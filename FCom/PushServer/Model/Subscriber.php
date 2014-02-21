<?php

class FCom_PushServer_Model_Subscriber extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_subscriber';
    static protected $_origClass = __CLASS__;

    /**
     * - id
     * - channel_id
     * - client_id
     */
    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('create_at', BDb::now(), 'IFNULL');
        $this->set('update_at', BDb::now());

        return true;
    }
}
