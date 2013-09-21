<?php

/*
- id
- user_id
- status
- message
- create_at // when user joined the session
- update_at // the last time user got updated on chat
*/

class FCom_AdminChat_Model_UserStatus extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_adminchat_userstatus';
    static protected $_origClass = __CLASS__;


    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('create_at', BDb::now(), null);
        $this->set('update_at', BDb::now());

        return true;
    }
}
