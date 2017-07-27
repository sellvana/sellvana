<?php

class Sellvana_Email_Model_Mailing_Event extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_mailing_event';

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) {
            return false;
        }

        if (!$this->get('remote_ip')) {
            $this->set('remote_ip', $this->BRequest->ip());
        }

        return true;
    }

}