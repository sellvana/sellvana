<?php

class FCom_Customer_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
        BMigrate::upgrade('0.1.1', '0.1.2', array($this, 'upgrade_0_1_2'));
    }

    public function install()
    {
        FCom_Customer_Model_Customer::i()->install();
        FCom_Customer_Model_Address::i()->install();
    }

    public function upgrade_0_1_1()
    {
        FCom_Customer_Model_Address::upgrade_0_1_1();
    }

    public function upgrade_0_1_2()
    {
        FCom_Customer_Model_Customer::upgrade_0_1_2();
    }
}