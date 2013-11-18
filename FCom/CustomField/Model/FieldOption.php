<?php

class FCom_CustomField_Model_FieldOption extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_field_option';

    public function getListAssocById($fieldId)
    {
        $result = array();
        $options = $this->orm()->where("field_id", $fieldId)->find_many();
        foreach($options as $o) {
            $result[$o->label] = $o->label;
        }
        return $result;
    }
    public function getListAssoc()
    {
        $result = array();
        $options = $this->orm()->find_many();
        foreach($options as $o) {
            $result[$o->field_id][] = $o->label;
        }
        return $result;
    }
}
