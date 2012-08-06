<?php

class FCom_Market_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        $tModules = FCom_Market_Model_Modules::table();
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

}