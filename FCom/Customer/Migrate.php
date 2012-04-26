<?php

class FCom_Customer_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.1', array($this, 'install'));
    }

    public function install()
    {
        FCom_Customer_Model_Customer::i()->install();
        FCom_Customer_Model_Address::i()->install();
    }

    public function upgrade_0_1_1()
    {
        BDb::run("
ALTER TABLE ".FCom_Customer_Model_Address::table()."
    ADD COLUMN `lat` DECIMAL(15,10) NULL AFTER `update_dt`,
    ADD COLUMN `lng` DECIMAL(15,10) NULL AFTER `lat`;
        ");
    }
}