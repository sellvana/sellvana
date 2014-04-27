<?php
/*
- id
- chat_id // chat session
- user_id // who sent the message
- text // message text
- create_at // message time
*/
class FCom_AdminChat_Model_History extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_adminchat_history';
    static protected $_origClass = __CLASS__;

    public function onBeforeSave()
    {
        if ( !parent::onBeforeSave() ) return false;

        $this->set( 'create_at', BDb::now(), 'IFNULL' );
        $this->set( 'update_at', BDb::now() );

        return true;
    }
}
