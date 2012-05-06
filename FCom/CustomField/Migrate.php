<?php

class FCom_CustomField_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install() {
        FCom_CustomField_Model_Field::i()->install();
        FCom_CustomField_Model_FieldOption::i()->install();
        FCom_CustomField_Model_Set::i()->install();
        FCom_CustomField_Model_SetField::i()->install();
        FCom_CustomField_Model_ProductField::i()->install();
    }
}