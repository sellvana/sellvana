<?php

class FCom_IndexTank_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        //Install IndexTank product index and functions
        FCom_IndexTank_Index_Product::i()->install();

        //create table
        $pFields = FCom_IndexTank_Model_ProductFields::table();
        BDb::run( "
            CREATE TABLE IF NOT EXISTS {$pFields} (
            `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
            `field_name` varchar(1024) NOT NULL DEFAULT '',
            `type` enum('fulltext','category','variable') NOT NULL,
            `priority` int(11) unsigned NOT NULL DEFAULT '1',
            `show` enum('','link','checkbox') NOT NULL DEFAULT '',
            `filter` enum('','inclusive','exclusive') NOT NULL DEFAULT ''
            PRIMARY KEY (`id`)
            )ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

    }
}