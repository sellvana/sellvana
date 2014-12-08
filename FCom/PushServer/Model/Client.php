<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PushServer_Model_Client
 *
 * @property int $id
 * @property string $session_id
 * @property string $status
 * @property int $admin_user_id
 * @property int $customer_id
 * @property string $remote_ip
 * @property string $create_at
 * @property string $update_at
 * @property string $data_serialized
 *
 * DI
 * @property FCom_PushServer_Main $FCom_PushServer_Main
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 * @property FCom_PushServer_Model_Message $FCom_PushServer_Model_Message
 * @property FCom_PushServer_Model_Subscriber $FCom_PushServer_Model_Subscriber
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class FCom_PushServer_Model_Client extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_client';
    static protected $_origClass = __CLASS__;

    static protected $_clientCache = [];
    static protected $_windowName;
    static protected $_connId;

    /**
     * @var FCom_PushServer_Model_Message[]
     */
    protected $_messages = [];

    /**
     * Get or create client record for current browser session
     * @return FCom_PushServer_Model_Client
     */
    public function sessionClient()
    {
        $sessId = $this->BSession->sessionId();

        /*todo: because we need get data from data_serialized which be updated from different connection, so temporary disable load from cache*/
        /*if (!empty(static::$_clientCache[$sessId])) {
            return static::$_clientCache[$sessId];
        }

        $sessData =& $this->BSession->dataToUpdate();
        if (!empty($sessData['pushserver']['client'])) {
            static::$_clientCache[$sessId] = $this->create($sessData['pushserver']['client'], false);
            return static::$_clientCache[$sessId];
        }*/

        $client = $this->load($sessId, 'session_id');
        if (!$client) {
            $client = $this->create([
                'session_id' => $sessId,
                'remote_ip' => $this->BRequest->ip(),
            ]);
        }
        if (!$client->get('admin_user_id') && class_exists('FCom_Admin_Model_User')) {
            $userId = $this->FCom_Admin_Model_User->sessionUserId();
            if ($userId) {
                $client->set('admin_user_id', $userId);
            }
        }
        if (!$client->get('customer_id') && class_exists('FCom_Customer_Model_Customer')) {
            $custId = $this->FCom_Customer_Model_Customer->sessionUserId();
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
     * @param $clientId
     * @throws BException
     * @return $this|bool
     */
    public function getClient($clientId)
    {
        if (is_object($clientId) && $clientId instanceof FCom_PushServer_Model_Client) {
            return $clientId;
        }
        if (!empty(static::$_clientCache[$clientId])) {
            return static::$_clientCache[$clientId];
        }
        $client = false;
        if (is_numeric($clientId)) {
            $client = $this->load($clientId);
        } elseif (is_string($clientId)) {
            $client = $this->load($clientId, 'session_id');
        }
        static::$_clientCache[$clientId] = $client;
        return $client;
    }

    /**
     * @return mixed
     */
    public function getWindowName()
    {
        return static::$_windowName;
    }

    /**
     * @return mixed
     */
    public function getConnId()
    {
        return static::$_connId;
    }

    /**
     * @param int|FCom_Admin_Model_User $user
     * @return FCom_PushServer_Model_Client[]
     */
    public function findByAdminUser($user)
    {
        if (is_object($user)) {
            $user = $user->id;
        }
        $result = $this->orm()->where('admin_user_id', $user)->find_many_assoc('session_id');
        return $result;
    }

    /**
     * @param int|FCom_Customer_Model_Customer $customer
     * @return array
     */
    public function findByCustomer($customer)
    {
        if (is_object($customer)) {
            $customer = $customer->id;
        }
        return $this->orm()->where('customer_id', $customer)->find_many_assoc('session_id');
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        /*if ($this->session_id === $this->BSession->sessionId()) {
            $sessData =& $this->BSession->dataToUpdate();
            $sessData['pushserver']['client'] = $this->as_array();
        }*/
    }

    /**
     * @param $request
     * @return $this
     */
    public function processRequest($request)
    {
        $client = $this->sessionClient();

        if (!isset($request['window_name']) || !isset($request['conn_id'])
            || !is_string($request['window_name']) || !is_numeric($request['conn_id'])
        ) {
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
        $services = $this->FCom_PushServer_Main->getServices();
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
                    $instance = $this->{$class};
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

                    if ($this->FCom_PushServer_Main->isDebugMode()) {
                        $this->BDebug->log("RECEIVE: " . get_class($instance) . '::' . $method . ': ' . print_r($message, 1));
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

        if($newWindows){
            foreach ($newWindows as $windowName => $window) { // some cleanup
                if (empty($window['connections'])) {
                    unset($newWindows[$windowName]);
                }
            }
        }

        foreach ($oldConnections as $connId => $conn) { // reset old connections
            unset($newWindows[static::$_windowName]['connections'][$connId]);
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
        //$this->BDebug->dump($this->getData('windows'));
        return $this;
    }

    /**
     * @return $this
     */
    public function waitForMessages()
    {
        $delay = $this->BConfig->get('modules/FCom_PushServer/delay_microsec', 100000);
        $timeout = $this->BConfig->get('modules/FCom_PushServer/poll_timeout', 50);
        $start = time();
        $this->_messages = $this->sync();
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
        $msgHlp = $this->FCom_PushServer_Model_Message;
        $where = ['client_id' => $this->get('id'), 'window_name' => (string)static::$_windowName, 'status' => 'published'];
        $msgHlp->update_many(['status' => 'locked'], $where);
        $where['status'] = 'locked';
        $messageModels = $msgHlp->orm('m')->where($where)->find_many_assoc();
        $messages = [];
        foreach ($messageModels as $msg) {

            if ($this->FCom_PushServer_Main->isDebugMode()) {
                $this->BDebug->log("SYNC: " . print_r($msg->as_array(), 1));
            }

            //$msg->set('status', 'sent')->save();
            $message = (array) $this->BUtil->fromJson($msg->get('data_serialized'));
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

    /**
     * @return $this
     * @throws BException
     */
    public function fetchCustomData()
    {
        $clientUpdate = $this->orm()->select('data_serialized')->where('id', $this->get('id'))->find_one();
        if ($clientUpdate) { // another connection just connected
            $data = (array) $this->BUtil->fromJson($clientUpdate->get('data_serialized'));
            $this->set(static::$_dataCustomField, $data);
        }
        return $this;
    }

    /**
     * @param $status
     * @return $this
     * @throws BException
     */
    public function setStatus($status)
    {
        $this->set('status', $status);
        $this->BEvents->fire(__METHOD__, ['client' => $this, 'status' => $status]);
        return $this;
    }

    /**
     * Subscribe the client to a channel
     * @param null $channel
     * @throws BException
     * @return $this
     */
    public function subscribe($channel = null)
    {
        if (null === $channel) {
            $channel = $this->getChannel();
        }
        $isSessionClient = $this->session_id === $this->BSession->sessionId();
        if (!is_object($channel)) {
            $channel = $this->FCom_PushServer_Model_Channel->getChannel($channel, true, $isSessionClient);
        }
        if ($isSessionClient) {
            $sessData =& $this->BSession->dataToUpdate();
            if (!empty($sessData['pushserver']['subscribed'][$channel->channel_name])) {
                return $this;
            } else {
                $sessData['pushserver']['subscribed'][$channel->channel_name] = true;
            }
        }
        $hlp = $this->FCom_PushServer_Model_Subscriber;
        $data = ['client_id' => $this->id(), 'channel_id' => $channel->id()];
        $subscriber = $hlp->loadWhere($data);
        if (!$subscriber) {
            try {
                $subscriber = $hlp->create($data)->save();
            } catch (Exception $e) {
                $this->resave();
                $channel->resave();
                $subscriber = $hlp->create($data)->save();
            }
        }
        return $this;
    }

    /**
     * Un-subscribe the client from the channel
     */
    public function unsubscribe($channel)
    {
        if (!is_object($channel)) {
            $channel = $this->FCom_PushServer_Model_Channel->getChannel($channel, true);
        }
        $data = ['client_id' => $this->id(), 'channel_id' => $channel->id()];
        $this->FCom_PushServer_Model_Subscriber->delete_many($data);

        if ($this->session_id === $this->BSession->sessionId()) {
            $sessData =& $this->BSession->dataToUpdate();
            unset($sessData['pushserver']['channels'][$channel->channel_name]);
            unset($sessData['pushserver']['subscribed'][$channel->channel_name]);
        }

        return $this;
    }

    /**
     * Send a message to the client
     * @param $message
     * @return $this
     */
    public function send($message)
    {
        $this->getChannel()->send($message);
        return $this;
    }

    /**
     * @return $this
     * @throws BException
     */
    public function getChannel()
    {
        return $this->FCom_PushServer_Model_Channel->getChannel('client:' . $this->id(), true, true);
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }
}
