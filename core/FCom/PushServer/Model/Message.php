<?php

/**
 * Class FCom_PushServer_Model_Message
 *
 * @property int $id
 * @property string $seq
 * @property int $channel_id
 * @property int $subscriber_id
 * @property int $client_id
 * @property string $window_name
 * @property int $conn_id
 * @property string $status
 * @property string $data_serialized
 * @property string $create_at
 * @property string $update_at
 */
class FCom_PushServer_Model_Message extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_message';
    static protected $_origClass = __CLASS__;

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('seq', microtime(true), 'IFNULL');

        return true;
    }
}
