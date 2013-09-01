<?php

class FCom_PushServer_Model_Message extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_message';
    static protected $_origClass = __CLASS__;

    /**
     * - id
     * - seq
     * - channel_id
     * - subscriber_id
     * - sender_client_id
     * - recipient_client_id
     * - data_serialized
     * - status
     * - create_at
     * - update_at
     */

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('seq', microtime(true), null);
        $this->set('create_at', BDb::now(), null);
        $this->set('update_at', BDb::now());

        return true;
    }
}
