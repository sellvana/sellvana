<?php

/**
 * Class FCom_PushServer_Model_Subscriber
 *
 * @property int $id
 * @property int $channel_id
 * @property int $client_id
 * @property string $status
 * @property string $create_at
 * @property string $update_at
 */
class FCom_PushServer_Model_Subscriber extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_subscriber';
    static protected $_origClass = __CLASS__;

}
