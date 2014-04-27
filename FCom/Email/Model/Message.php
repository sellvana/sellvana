<?php

class FCom_Email_Model_Message extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_email_message';
    static protected $_origClass = __CLASS__;

    public function onAfterLoad()
    {
        parent::onAfterLoad();

        $this->data = $this->data_serialized ? BUtil::fromJson( $this->data_serialized ) : [];
    }

    public function onBeforeSave()
    {
        if ( !parent::onBeforeSave() ) return false;

        if ( !$this->create_at ) $this->create_at = BDb::now();

        $this->data_serialized = BUtil::toJson( $this->data );

        return true;
    }
}
