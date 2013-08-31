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
            $client = static::create(array('session_id' => $sessId))->save();
        }
        return $client;
    }

    /**
     * Get client by id or session_id
     */
    static public function getClient($client)
    {
        if (is_object($client) && $client instanceof FCom_PushServer_Model_Client) {
            return $client;
        }
        if (is_numeric($client)) {
            return static::load($client);
        }
        if (is_string($client)) {
            return static::load($client, 'session_id');
        }
        throw new BException('Invalid client id');
    }

    static public function findByAdminUserId($user)
    {
        if (is_object($user)) {
            $user = $user->id;
        }
        return static::orm()->where('admin_user_id', $user)->find_many_assoc('session_id');
    }

    static public function findByCustomerId($customer)
    {
        if (is_object($customer)) {
            $customer = $customer->id;
        }
        return static::orm()->where('customer_id', $customer)->find_many_assoc('session_id');
    }

    public function dispatch($request)
    {
        $delay = BConfig::i()->get('modules/FCom_PushServer/delay_microsec');
        $this->checkIn($request);
        while (true) {
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
     * Check in the client and receive request from browser
     *
     * @param string $request
     */
    public function checkIn($request)
    {
        if (!empty($request['messages'])) {
            foreach ($request['messages'] as $message) {
                $this->receive($message);
            }
        }
        if ($this->get('status')==='connected') { // this client is already connected
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
                usleep(500000);
            }
        } else { // this is a new client
            $this->set('status', 'connected')->save(); // set as connected
        }
    }

    /**
     * Get messages to be sent to browser
     */
    public function sync()
    {
        $messageModels = FCom_PushServer_Model_Message::i()->orm('m')->where('recipient_client_id', $this->id)
            ->join('FCom_PushServer_Model_Channel', array('c.id','=','m.channel_id'), 'c')->select('c.channel_name')
            ->find_many_assoc();
        $messages = array();
        foreach ($messageModels as $model) {
            $model->set('status', 'sent')->save();
            $message = (array) BUtil::fromJson($model->get('data_serialized'));
            $message['seq'] = $model->get('seq');
            $message['channel'] = $model->get('channel_name');
            $message['ts'] = $model->get('create_at');
            $messages[] = $message;
        }

        $clientUpdate = static::orm()->select('handover')->where('id', $this->id)->find_one();
        if ($clientUpdate->get('handover')) { // another connection just connected
            $this->set('handover', 1); // update local instance
            $messages[] = array( // create message to exit loop
                'seq' => microtime(true),
                'channel' => 'session:' . $this->session_id,
                'ts' => BDb::now(),
                'message' => 'stop',
            );
        }

        return $messages;
    }

    /**
     * Check out the client
     */
    public function checkOut()
    {
        if ($this->get('handover_at')) { // do we need to hand over connection to another process?
            $this->set('handover_at', null)->save(); // clear the flag
        } else { // otherwise it is a normal disconnect
            $this->set('status', 'idle')->save(); // set client as idle //TODO: delete client immediately?
        }
    }

    /**
     * Subscribe the client to a channel
     */
    public function subscribe($channel)
    {
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
     * Receive a message from the client browser
     */
    public function receive($message)
    {
        try {
            if (empty($message['channel'])) {
                throw new BException('Empty message channel');
            }
            if (empty($message['seq'])) {
                throw new BException('Empty message seq number');
            }
            if ($message['channel']==='session') {
                $message['channel'] = 'session:' . $this->session_id;
            }

            $channel = FCom_PushServer_Model_Channel::i()->getChannel($message['channel']);

            BEvents::i()->fire(__METHOD__ . ':' . $message['channel'], array(
                'message' => $message,
                'client'  => $this,
                'channel' => $channel,
            ));

            if ($channel) {
                $channel->send($message, $this);
            }

            if (!($message['channel'] === 'session:' . $this->session_id && $message['message'] === 'received')) {
                $this->send(array('seq' => $message['seq'], 'message' => 'received'));
            }
        } catch (Exception $e) {
            $this->send(array('seq' => $message['seq'], 'message' => 'error', 'description' => $e->getMessage()));
        }
        return $this;
    }

    /**
     * Send a message to the client
     */
    public function send($message)
    {
        $message['channel'] = 'session:' . $this->session_id;
        $channel = FCom_PushServer_Model_Channel::i()->getChannel($message['channel'], true)->send($message);
        return $this;
    }
}
