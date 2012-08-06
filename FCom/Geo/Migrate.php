<?php

class FCom_Geo_Migrate extends BClass
{
    public function run()
    {
        BMigrate::i()->install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        $tCountry = FCom_Geo_Model_Country::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tCountry} (
            `iso` char(2) NOT NULL,
            `iso3` char(3) DEFAULT NULL,
            `numcode` smallint(6) DEFAULT NULL,
            `name` varchar(80) NOT NULL,
            PRIMARY KEY (`iso`),
            KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tRegion = FCom_Geo_Model_Region::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tRegion} (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `country` char(2) NOT NULL,
            `code` varchar(10) DEFAULT NULL,
            `name` varchar(40) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `country_code` (`country`,`code`),
            KEY `name_country` (`name`,`country`)
            ) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8;
        ");
    }
}