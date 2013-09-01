<?php

class FCom_PushServer_Model_Client extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_client';
    static protected $_origClass = __CLASS__;

    static protected $_clientCache = array();
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
        $client = static::load($sessId, 'session_id');
        if (!$client) {
            $client = static::create(array(
                'session_id' => $sessId,
                'remote_ip' => BRequest::i()->ip(),
            ))->save();
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

    public function dispatch()
    {
        $delay = BConfig::i()->get('modules/FCom_PushServer/delay_microsec');
        $this->checkIn();
        $start = time();
        while (true) {
            // if (time() - $start > 60) {
            //     break;
            // }
            if (connection_aborted()) {
                $this->set('status', 'offline')->save();
                break;
            }
            $messages = $this->sync();
            if ($messages) {
                break;
            } else {
                usleep($delay ? $delay : 500000);
            }
        }
        $this->checkOut();
        return array('messages' => $messages);
    }

    /**
     * Check in the client
     *
     */
    public function checkIn()
    {
        if ($this->get('status')==='online') { // this client is already connected
            // update client db record to hand over the connection
            $this->set('handover', 1)->save();
            $start = microtime(true);
            while (true) { // wait until other connection will close with results of received messages
                $clientUpdate = static::orm()->select('handover')->where('id', $this->id)->find_one();
                if ($clientUpdate->get('handover') == 0) {
                    break;
                }
                if (microtime(true)-$start > 1) {
                    $this->set('handover', 0)->save();
                    break;
                }
                usleep(300000);
            }
        } else { // this is a new client
            $this->subscribe();
            $this->set('status', 'online')->save(); // set as connected
        }
    }

    /**
     * Get messages to be sent to browser
     */
    public function sync()
    {
        $messageModels = FCom_PushServer_Model_Message::i()->orm('m')
            ->where('recipient_client_id', $this->id)
            ->find_many_assoc();
        $messages = array();
        foreach ($messageModels as $model) {
            $model->set('status', 'sent')->save();
            $message = (array) BUtil::fromJson($model->get('data_serialized'));
            //$message['ts'] = $model->get('create_at');
            $messages[] = $message;
            if (empty($message['seq'])) {
                $model->delete();
            }
        }

        $clientUpdate = static::orm()->select('handover')->where('id', $this->id)->find_one();
        if ($clientUpdate && $clientUpdate->get('handover')) { // another connection just connected
            $this->set('handover', 1); // update local instance
            $messages[] = array( // create message to exit loop
                'channel' => 'session',
                'signal' => 'handover',
            );
        }
        return $messages;
    }

    /**
     * Check out the client
     */
    public function checkOut()
    {
        if ($this->get('handover')) { // do we need to hand over connection to another process?
            $this->set('handover', 0)->save(); // clear the flag
        } else { // otherwise it is a normal disconnect
            $this->set('status', 'offline')->save(); // set client as idle //TODO: delete client immediately?
        }
    }

    /**
     * Subscribe the client to a channel
     */
    public function subscribe($channel = null)
    {
        if (is_null($channel)) {
            $channel = 'session:' . $this->session_id;
        }
        if (!is_object($channel)) {
            $channel = FCom_PushServer_Model_Channel::i()->getChannel($channel, true);
        }
        $hlp = FCom_PushServer_Model_Subscriber::i();
        $data = array('client_id' => $this->id, 'channel_id' => $channel->id);
        $subscriber = $hlp->load($data);
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
        $data = array('client_id' => $this->id, 'channel_id' => $channel->id);
        FCom_PushServer_Model_Subscriber::i()->delete_many($data);
        return $this;
    }

    /**
     * Send a message to the client
     */
    public function send($message)
    {
        $channel = FCom_PushServer_Model_Channel::i()->getChannel('session:' . $this->session_id);
        $channel->send($message);
        return $this;
    }
}
