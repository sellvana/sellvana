<?php

class FCom_Email_Migrate extends BClass
{
    public function run()
    {
        BMigrate::i()->install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        $tPref = FCom_Email_Model_Pref::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tPref} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
            `unsub_all` tinyint(4) NOT NULL,
            `sub_newsletter` tinyint(4) NOT NULL,
            `create_dt` datetime NOT NULL,
            `update_dt` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}