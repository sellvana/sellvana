<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PushServer_Model_Channel
 *
 * @property string $id
 * @property string $channel_name
 * @property string $channel_out
 * @property string $data_serialized
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
 * @property string $create_at
 * @property string $update_at
 *
 * DI
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 * @property FCom_PushServer_Model_Message $FCom_PushServer_Model_Message
 * @property FCom_PushServer_Main $FCom_PushServer_Main
 */
class FCom_PushServer_Model_Channel extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_channel';
    static protected $_origClass = __CLASS__;

    static protected $_channelCache = [];

    /**
     * @param $channel
     * @param bool $create
     * @param bool $session
     * @return $this
     * @throws BException
     */
    public function getChannel($channel, $create = false, $session = false)
    {
        if (is_object($channel) && ($channel instanceof FCom_PushServer_Model_Channel)) {
            return $channel;
        } elseif (!is_string($channel)) {
            throw new BException('Invalid channel identifier: ' . print_r($channel, 1));
        }
        $channelName = $channel;
        if (!empty(static::$_channelCache[$channelName])) {
            return static::$_channelCache[$channelName];
        }
        $sessData =& $this->BSession->dataToUpdate();
        if (!empty($sessData['pushserver']['channels'][$channelName])) {
            static::$_channelCache[$channelName] = $this->create($sessData['pushserver']['channels'][$channelName], false);
            return static::$_channelCache[$channelName];
        }
        $channel = $this->load($channelName, 'channel_name');
        if (!$channel) {
            $channel = $this->create(['channel_name' => $channelName])->save();
        }
        static::$_channelCache[$channelName] = $channel;
        if ($session) {
            $sessData['pushserver']['channels'][$channelName] = $channel->as_array();
        }
        return $channel;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        $sessData =& $this->BSession->dataToUpdate();
        if (!empty($sessData['pushserver']['channels'][$this->channel_name])) {
            $sessData['pushserver']['channels'][$this->channel_name] = $this->as_array();
        }
    }

    public function onBeforeDelete()
    {
        if (!parent::onBeforeDelete()) return false;

        $this->send(['signal' => 'delete']);

        return true;
    }

    /**
     * @param $callback
     * @return $this
     */
    public function listen($callback)
    {
        $channelName = $this->channel_name;
        $this->BEvents->on('FCom_PushServer_Model_Channel::send:' . $channelName, $callback);
        return $this;
    }

    /**
     * @param $client
     * @return $this
     */
    public function subscribe($client)
    {
        $this->FCom_PushServer_Model_Client->getClient($client)->subscribe($this);
        return $this;
    }

    /**
     * @param $client
     * @return $this
     */
    public function unsubscribe($client)
    {
        $this->FCom_PushServer_Model_Client->getClient($client)->unsubscribe($this);
        return $this;
    }

    /**
     * @param array $message
     * @param FCom_PushServer_Model_Client $fromClient
     * @return $this
     */
    public function send($message, $fromClient = null)
    {
        if (empty($message['channel'])) {
            $message['channel'] = $this->channel_name;
        }


        if ($this->FCom_PushServer_Main->isDebugMode()) {
            $this->BDebug->log("SEND1: " . print_r($message, 1));
        }
        $this->BEvents->fire(__METHOD__ . ':' . $this->get('channel_name'), [
            'channel' => $this,
            'message' => $message,
            'client' => $fromClient,
        ]);

        $clientHlp = $this->FCom_PushServer_Model_Client;
        $fromWindowName = $clientHlp->getWindowName();
        $fromConnId = $clientHlp->getConnId();
        $msgHlp = $this->FCom_PushServer_Model_Message;
        $msgIds = [];

        $toClients = $this->FCom_PushServer_Model_Client->orm('c')
            ->join('FCom_PushServer_Model_Subscriber', ['c.id', '=', 's.client_id'], 's')
            ->where('s.channel_id', $this->id())
            ->select('s.id', 'sub_id')->select('c.id')->select('c.data_serialized')
            ->find_many();

        if ($this->FCom_PushServer_Main->isDebugMode()) {
            $this->BDebug->log('SEND2: ' . sizeof($toClients) . ': ' . print_r($this->as_array(), 1));
        }

        foreach ($toClients as $toClient) {
            /** @var FCom_PushServer_Model_Client $toClient */
            if ($fromClient && $fromClient->id() === $toClient->id()) {
                //continue;
            }
            $windows = (array)$toClient->getData('windows');
            foreach ($windows as $toWindowName => $toWindowData) {
                $toConnId = !empty($toWindowData['connections']) ? key($toWindowData['connections']) : null;
                $msg = $msgHlp->create([
                    'seq' => !empty($message['seq']) ? $message['seq'] : null,
                    'channel_id' => $this->id(),
                    'subscriber_id' => $toClient->get('sub_id'),
                    'client_id' => $toClient->id(),
                    'window_name' => $toWindowName,
                    'conn_id' => $toConnId,
                    'status' => 'published',
                ])->setData($message)->save();
                //$msgIds[] = $msg->id;

                if ($this->FCom_PushServer_Main->isDebugMode()) {
                    $this->BDebug->log("SEND3: " . print_r($msg->as_array(), 1));
                }
            }
        }
        if ($msgIds) {
            $msgHlp->update_many(['status' => 'published'], ['id' => $msgIds]);
        }
        return $this;
    }
}
