<?php

class Sellvana_Email_Model_Mailing_Subscriber extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_mailing_subscriber';

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) {
            return false;
        }

        if (!$this->get('unique_id')) {
            $this->set('unique_id', $this->BUtil->randomString(16));
        }

        return true;
    }
}