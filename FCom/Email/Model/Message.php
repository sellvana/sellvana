<?php

class FCom_Email_Model_Message extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_email_message';
    static protected $_origClass = __CLASS__;

    public function afterLoad()
    {
        parent::afterLoad();

        $this->data = $this->data_serialized ? BUtil::fromJson($this->data_serialized) : array();
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        if (!$this->create_dt) $this->create_dt = BDb::now();

        $this->data_serialized = BUtil::toJson($this->data);

        return true;
    }
}
