<?php

class FCom_PushServer_Model_Client extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_client';
    static protected $_origClass = __CLASS__;

    static protected $_clientCache = [];
    static protected $_windowName;
    static protected $_connId;

    protected $_messages = [];
    /**
     * - id
     * - session_id
     * - status
     * - handover
     * - admin_user_id
     * - customer_id
     * - create_at
     * - update_at
     */

    /**
     * Get or create client record for current browser session
     */
    static public function sessionClient()
    {
        $sessId = BSession::i()->sessionId();
        if (!empty(static::$_clientCache[$sessId])) {
            return static::$_clientCache[$sessId];
        }

        $sessData =& BSession::i()->dataToUpdate();
        if (!empty($sessData['pushserver']['client'])) {
            static::$_clientCache[$sessId] = static::create($sessData['pushserver']['client'], false);
            return static::$_clientCache[$sessId];
        }

        $client = static::load($sessId, 'session_id');
        if (!$client) {
            $client = static::create([
                'session_id' => $sessId,
                'remote_ip' => BRequest::i()->ip(),
            ]);
        }
        if (!$client->get('admin_user_id') && class_exists('FCom_Admin_Model_User')) {
            $userId = FCom_Admin_Model_User::i()->sessionUserId();
            if ($userId) {
                $client->set('admin_user_id', $userId);
            }
        }
        if (!$client->get('customer_id') && class_exists('FCom_Customer_Model_Customer')) {
            $custId = FCom_Customer_Model_Customer::i()->sessionUserId();
            if ($custId) {
                $client->set('customer_id', $custId);
            }
        }
        $client->save();
        static::$_clientCache[$sessId] = $client;
        static::$_clientCache[$client->id()] = $client;
        return $client;
    }

    /**
     * Get client by id or session_id
     */
    static public function getClient($clientId)
    {
        if (is_object($clientId) && $clientId instanceof FCom_PushServer_Model_Client) {
            return $clientId;
        }
        if (!empty(static::$_clientCache[$clientId])) {
            return static::$_clientCache[$clientId];
        }
        $client = false;
        if (is_numeric($clientId)) {
            $client = static::load($clientId);
        } elseif (is_string($clientId)) {
            $client = static::load($clientId, 'session_id');
        }
        static::$_clientCache[$clientId] = $client;
        return $client;
    }

    static public function getWindowName()
    {
        return static::$_windowName;
    }

    static public function getConnId()
    {
        return static::$_connId;
    }

    static public function findByAdminUser($user)
    {
        if (is_object($user)) {
            $user = $user->id;
        }
        $result = static::orm()->where('admin_user_id', $user)->find_many_assoc('session_id');
        return $result;
    }

    static public function findByCustomer($customer)
    {
        if (is_object($customer)) {
            $customer = $customer->id;
        }
        return static::orm()->where('customer_id', $customer)->find_many_assoc('session_id');
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        if ($this->session_id === BSession::i()->sessionId()) {
            $sessData =& BSession::i()->dataToUpdate();
            $sessData['pushserver']['client'] = $this->as_array();
        }
    }

    public function processRequest($request)
    {
        $client = FCom_PushServer_Model_Client::i()->sessionClient();

        if (!isset($request['window_name']) || !isset($request['conn_id'])) {
            $client->send([
                'signal' => 'error',
                'description' => 'Missing window_name or conn_id',
            ]);
            return;
        }
        static::$_windowName = $request['window_name'];
        static::$_connId = $request['conn_id'];

        if (empty($request['messages'])) {
            return $this;
        }
        $services = FCom_PushServer_Main::i()->getServices();
        foreach ($request['messages'] as $message) {
            try {
                foreach ($services as $service) {
                    if ($service['channel'] !== $message['channel']
                        && !($service['is_pattern'] && preg_match($service['channel'], $message['channel']))
                    ) {
                        continue;
                    }
                    if (is_callable($service['callback'])) {
                        call_user_func($service['callback'], $message);
                        continue;
                    }
                    if (!class_exists($service['callback'])) {
                        continue;
                    }
                    $class = $service['callback'];
                    $instance = $class::i();
                    if (!($instance instanceof FCom_PushServer_Service_Abstract)) {
                        //TODO: exception?
                        continue;
                    }

                    $instance->setMessage($message, $client);

                    if (!$instance->onBeforeDispatch()) {
                        continue;
                    }

                    if (!empty($message['signal'])) {
                        $method = 'signal_' . $message['signal'];
                        if (!method_exists($class, $method)) {
                            $method = 'onUnknownSignal';
                        }
                    } else {
                        $method = 'onUnknownSignal';
                    }

                    if (FCom_PushServer_Main::isDebugMode()) {
                        BDebug::log("RECEIVE: " . get_class($instance) . '::' . $method . ': ' . print_r($message, 1));
                    }
                    $instance->$method();

                    $instance->onAfterDispatch();
                }
            } catch (Exception $e) {
                $client->send([
                    'ref_seq' => !empty($message['seq']) ? $message['seq'] : null,
                    'ref_signal' => !empty($message['signal']) ? $message['signal'] : null,
                    'signal' => 'error',
                    'description' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                ]);
            }
        }

        return $this;
    }

    /**
     * Check in the client
     *
     */
    public function checkIn()
    {
        $oldWindows = $newWindows = (array) $this->getData('windows');
        $oldConnections = !empty($oldWindows[static::$_windowName]['connections'])
            ? $oldWindows[static::$_windowName]['connections'] : [];

        foreach ($newWindows as $windowName => $window) { // some cleanup
            if (empty($window['connections'])) {
                unset($newWindows[$windowName]);
            }
        }

        foreach ($oldConnections as $connId => $conn) { // reset old connections
            $newWindows[static::$_windowName]['connections'][$connId] = 0;
        }
        $newWindows[static::$_windowName]['connections'][static::$_connId] = 1; // set new connection

        $this->setData('windows', $newWindows)->save(); // save new state

        if (!$oldWindows) { // is this first connection for the client
            $this->subscribe();
            $this->set('status', 'online')->save(); // set as connected
        } elseif (false && $oldConnections) { // are there already connections for this window
            $start = microtime(true);
            $connKey = 'windows/' . static::$_windowName . '/connections';
            while (true) {
                $this->fetchCustomData(); // update connections
                $newConnections = $this->getData($connKey);
                if (!$newConnections || sizeof($newConnections) === 1) { // only this connection left or no connections at all
                    break;
                }
                if (microtime(true) - $start > 1) { // timeout for waiting for other connections to reset
                    foreach ($newConnections as $connId => $conn) { // remove old connections
                        unset($newConnections[$connId]);
                    }
                    $this->setData($connKey, $newConnections)->save();
                    break;
                }
                usleep(300000);
            }
        }
//BDebug::dump($this->getData('windows'));
        return $this;
    }

    public function waitForMessages()
    {
        $delay = BConfig::i()->get('modules/FCom_PushServer/delay_microsec', 100000);
        $timeout = BConfig::i()->get('modules/FCom_PushServer/poll_timeout', 50);
        $start = time();
        while (true) {
            if (time() - $start > $timeout) { // timeout for connection to counteract default gateway timeouts
                break;
            }
            if (connection_aborted()) { // browser cancelled connection
                break;
            }
            $this->_messages = $this->sync(); // fetch messages for the client
            if ($this->_messages) {
                break;
            } else {
                usleep($delay);
            }
        }
        return $this;
    }

    /**
     * Get messages to be sent to browser
     */
    public function sync()
    {
        $msgHlp = FCom_PushServer_Model_Message::i();
        $where = ['client_id' => $this->get('id'), 'window_name' => static::$_windowName, 'status' => 'published'];
        $msgHlp->update_many(['status' => 'locked'], $where);
        $where['status'] = 'locked';
        $messageModels = $msgHlp->orm('m')->where($where)->find_many_assoc();
        $messages = [];
        foreach ($messageModels as $msg) {

            if (FCom_PushServer_Main::isDebugMode()) {
                BDebug::log("SYNC: " . print_r($msg->as_array(), 1));
            }

            //$msg->set('status', 'sent')->save();
            $message = (array) BUtil::fromJson($msg->get('data_serialized'));
            //$message['ts'] = $model->get('create_at');
            $messages[] = $message;
            // if (empty($message['seq'])) {
            //     $msg->delete();
            // }
        }
        $msgHlp->delete_many($where);

        $this->fetchCustomData();
        $connKey = 'windows/' . static::$_windowName . '/connections';
        $connections = $this->getData($connKey);
        if (empty($connections[static::$_connId])) { // this connection was removed
            unset($connections[static::$_connId]);
            $this->setData($connKey, $connections);
            if (!$messages) {
                $messages[] = ['channel' => 'client', 'signal' => 'noop'];
            }
        }
        // foreach ($connections as $connId => $conn) {
        //     if ($connId > static::$_connId) { // a new connection was made
        //         $messages[] = array('channel' => 'client', 'signal' => 'noop');
        //         break;
        //     }
        // }

        return $messages;
    }

    /**
     * Check out the client
     */
    public function checkOut()
    {
        $windows = $this->getData('windows');
        unset($windows[static::$_windowName]['connections'][static::$_connId]);
        if (empty($windows[static::$_windowName]['connections'])) {
            unset($windows[static::$_windowName]);
        }

        $this->setData('windows', $windows);

        if (!$windows && !$this->_messages) {
            $this->setStatus('offline');
        }

        $this->save();

        return $this;
    }

    public function fetchCustomData()
    {
        $clientUpdate = static::orm()->select('data_serialized')->where('id', $this->get('id'))->find_one();
        if ($clientUpdate) { // another connection just connected
            $data = (array) BUtil::fromJson($clientUpdate->get('data_serialized'));
            $this->set(static::$_dataCustomField, $data);
        }
        return $this;
    }

    public function setStatus($status)
    {
        $this->set('status', $status);
        BEvents::i()->fire(__METHOD__, ['client' => $this, 'status' => $status]);
        return $this;
    }

    /**
     * Subscribe the client to a channel
     */
    public function subscribe($channel = null)
    {
        if (null === $channel) {
            $channel = $this->getChannel();
        }
        $isSessionClient = $this->session_id === BSession::i()->sessionId();
        if (!is_object($channel)) {
            $channel = FCom_PushServer_Model_Channel::i()->getChannel($channel, true, $isSessionClient);
        }
        if ($isSessionClient) {
            $sessData =& BSession::i()->dataToUpdate();
            if (!empty($sessData['pushserver']['subscribed'][$channel->channel_name])) {
                return $this;
            } else {
                $sessData['pushserver']['subscribed'][$channel->channel_name] = true;
            }
        }
        $hlp = FCom_PushServer_Model_Subscriber::i();
        $data = ['client_id' => $this->id(), 'channel_id' => $channel->id()];
        $subscriber = $hlp->loadWhere($data);
        if (!$subscriber) {
            $subscriber = $hlp->create($data)->save();
        }
        return $this;
    }

    /**
     * Unsubscribe the client from the channel
     */
    public function unsubscribe($channel)
    {
        if (!is_object($channel)) {
            $channel = FCom_PushServer_Model_Channel::i()->getChannel($channel, true);
        }
        $data = ['client_id' => $this->id(), 'channel_id' => $channel->id()];
        FCom_PushServer_Model_Subscriber::i()->delete_many($data);

        if ($this->session_id === BSession::i()->sessionId()) {
            $sessData =& BSession::i()->dataToUpdate();
            unset($sessData['pushserver']['channels'][$channel->channel_name]);
            unset($sessData['pushserver']['subscribed'][$channel->channel_name]);
        }

        return $this;
    }

    /**
     * Send a message to the client
     */
    public function send($message)
    {
        $this->getChannel()->send($message);
        return $this;
    }

    public function getChannel()
    {
        return FCom_PushServer_Model_Channel::i()->getChannel('client:' . $this->id(), true, true);
    }

    public function getMessages()
    {
        return $this->_messages;
    }
}
