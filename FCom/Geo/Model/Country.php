<?php

class FCom_Geo_Model_Country extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_geo_country';
    protected static $_origClass = __CLASS__;

    protected static $_optionsCache = array();

    public static function options()
    {
        if (!static::$_optionsCache) {
            static::$_optionsCache = static::orm('c')->find_many_assoc('iso', 'name');
        }
        return static::$_optionsCache;
    }

    public static function getIsoByName($name)
    {
        static $countries;
        if (!$countries) {
            $countries = array_flip(static::options());
        }
        return !empty($countries[$name]) ? $countries[$name] : null;
    }

    public function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `iso` char(2) NOT NULL,
  `iso3` char(3) DEFAULT NULL,
  `numcode` smallint(6) DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  PRIMARY KEY (`iso`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}