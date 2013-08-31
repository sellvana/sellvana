<?php

class FCom_PushServer_Model_Channel extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_channel';
    static protected $_origClass = __CLASS__;

    static protected $_channelCache = array();

    /**
     * - id
     * - channel_name
     * - channel_out
     * - create_at
     * - update_at
     * - data_serialized
     *   - permissions
     *     - can_subscribe
     *       - everyone
     *       - admin_user
     *       - customer
     *       - none
     *     - can_publish
     *       - everyone
     *       - admin_user
     *       - customer
     *       - none
     *   - subscribers
     *   - message_queue
     */

    public function getChannel($channel, $create=false)
    {
        if (is_object($channel) && ($channel instanceof FCom_PushServer_Model_Channel)) {
            return $channel;
        }
        if (!empty(static::$_channelCache[$channel])) {
            return static::$_channelCache[$channel];
        }
        if (is_string($channel)) {
            $channelName = $channel;
            $channel = static::load($channel, 'channel_name');
            if (!$channel) {
                $channel = static::create(array('channel_name' => $channelName))->save();
            }
            static::$_channelCache[$channelName] = $channel;
        }
        return $channel;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('create_at', BDb::now(), null);
        $this->set('update_at', BDb::now());

        return true;
    }

    public function subscribeService($callback)
    {
        $channelName = $this->channel_name;
        //BEvents::i()->on('FCom_PushServer_Model_Client::receive:' . $channelName, $callback, array('context' => 'client:receive'));
        BEvents::i()->on('FCom_PushServer_Model_Channel::send:' . $channelName, $callback, array('context' => 'channel:send'));
        return $this;
    }

    public function subscribeClient($client)
    {
        FCom_PushServer_Model_Client::i()->getClient($client)->subscribe($this);
        return $this;
    }

    public function unsubscribeClient($client)
    {
        FCom_PushServer_Model_Client::i()->getClient($client)->unsubscribe($this);
        return $this;
    }

    public function send($message, $client = null)
    {
        BEvents::i()->fire(__METHOD__ . ':' . $this->channel_name, array(
            'channel' => $this,
            'message' => $message,
            'client'  => $client,
        ));

        $msgHlp = FCom_PushServer_Model_Message::i();
        $subscribers = FCom_PushServer_Model_Subscriber::i()->orm()->where('s.channel_id', $this->id)->find_many();
        foreach ($subscribers as $sub) {
            if ($client && $client->id === $sub->client_id) {
                continue;
            }
            $msg = $msgHlp->create(array(
                'seq' => $message['seq'],
                'channel_id' => $this->id,
                'subscriber_id' => $sub->id,
                'sender_client_id' => $client ? $client->id : null,
                'recipient_client_id' => $sub->client_id,
                'status' => 'published',
            ))->setData(BUtil::arrayMask($message, 'channel,seq,ts'), true)->save();
        }
        return $this;
    }
}
