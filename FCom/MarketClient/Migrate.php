<?php

class FCom_MarketClient_Migrate extends BClass
{
    public function install__0_1_0()
    {
        $tModules = FCom_MarketClient_Model_Modules::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tModules} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `mod_name` VARCHAR( 255 ) NOT NULL DEFAULT '',
            `name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `version` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `description` text COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $pModules = FCom_MarketClient_Model_Modules::table();
        BDb::run( " ALTER TABLE {$pModules} ADD `need_upgrade` tinyint(1) NOT NULL DEFAULT '0'");
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $pModules = FCom_MarketClient_Model_Modules::table();
        BDb::run( " ALTER TABLE {$pModules} MODIFY `description` text DEFAULT NULL");
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $pModules = FCom_MarketClient_Model_Modules::table();
        BDb::run( " ALTER TABLE {$pModules} ADD `market_version` varchar(50) DEFAULT NULL");
    }

    public function upgrade__0_1_3__0_1_4()
    {
        $pModules = FCom_MarketClient_Model_Modules::table();
        BDb::ddlTableDef($pModules, array(
            'COLUMNS' => array(
                'mod_name' => "varchar(255) NOT NULL AFTER `id`",
            ),
        ));
    }
}
