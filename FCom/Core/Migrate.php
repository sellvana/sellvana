<?php

class FCom_Core_Migrate extends BClass
{
    public function run()
    {
        BMigrate::i()->install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        $tMediaLibrary = FCom_Core_Model_MediaLibrary::table();
        BDb::run("
            CREATE TABLE {$tMediaLibrary} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `folder` varchar(32) NOT NULL,
            `subfolder` varchar(32) DEFAULT NULL,
            `file_name` varchar(255) NOT NULL,
            `file_size` int(11) DEFAULT NULL,
            `data_json` text,
            PRIMARY KEY (`id`),
            KEY `folder_file` (`folder`,`file_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}