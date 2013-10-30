<?php

class FCom_CustomField_Model_Set extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_fieldset';

    public function addSet($data)
    {
        $set = static::load(BUtil::arrayMask($data, 'set_type,set_code'));
        if (!$set) {
            $set = static::create($data)->save();
        } else {
            $set->set($data)->save();
        }
        return $set;
    }
}
