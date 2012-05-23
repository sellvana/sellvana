<?php

class FCom_Admin_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.2', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
        BMigrate::upgrade('0.1.1', '0.1.2', array($this, 'upgrade_0_1_2'));
    }

    public function install()
    {
        FCom_Admin_Model_Role::i()->install();
        FCom_Admin_Model_User::i()->install();
        FCom_Admin_Model_Personalize::i()->install();
    }

    public function upgrade_0_1_1()
    {
        $tUser = FCom_Admin_Model_User::table();

        try {
            BDb::run("
ALTER TABLE {$tUser}
ADD COLUMN `is_superadmin` TINYINT DEFAULT 0 NOT NULL AFTER `username`
, ADD COLUMN `role_id` INT NULL AFTER `is_superadmin`
, ADD COLUMN `token` varchar(20) DEFAULT NULL
;
            ");
        } catch (Exception $e) { }

        FCom_Admin_Model_Role::i()->install();
        BDb::run("
UPDATE {$tUser} SET is_superadmin=1;
        ");
    }

    public function upgrade_0_1_2()
    {
        $tUser = FCom_Admin_Model_User::table();
        try {
            BDb::run("
ALTER TABLE {$tUser} ADD COLUMN `token_dt` DATETIME NULL AFTER `token`;
            ");
        } catch (Exception $e) { }
    }
}