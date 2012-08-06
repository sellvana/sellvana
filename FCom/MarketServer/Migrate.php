<?php

class FCom_MarketServer_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        $accountTable = FCom_MarketServer_Model_Account::table();
        BDb::run( "
            CREATE TABLE IF NOT EXISTS {$accountTable} (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `customer_id` INT (11) UNSIGNED NOT NULL,
            `site_url` VARCHAR( 1024 ) NOT NULL DEFAULT '',
            `token` char(40) NOT NULL
            ) ENGINE = InnoDB;
         ");

        $tModules = FCom_MarketServer_Model_Modules::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tModules} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `version` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `description` text COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

}