<?php

class FCom_Customer_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        FCom_Customer_Model_Customer::i()->install();
        FCom_Customer_Model_Address::i()->install();
    }
}