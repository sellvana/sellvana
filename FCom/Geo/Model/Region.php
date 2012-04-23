<?php

class FCom_Geo_Model_Region extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_geo_region';
    protected static $_origClass = __CLASS__;

    protected static $_optionsCache = array();
    protected static $_allOptionsLoaded;

    public static function options($country)
    {
        if (!static::$_optionsCache[$country]) {
            static::$_optionsCache[$country] = static::orm('s')
                ->where('country', $country)->find_many_assoc('code', 'name');
        }
        return static::$_optionsCache[$country];
    }

    public static function allOptions()
    {
        if (!static::$_allOptionsLoaded) {
            $regions = static::orm('s')->find_many();
            foreach ($regions as $r) {
                static::$_optionsCache[$r->country][$r->code] = $r->name;
            }
        }
        return static::$_optionsCache;
    }

    public static function findByName($country, $name, $field=null)
    {
        $result = static::orm('s')->where('country', $country)->where('name', $name)->find_one();
        if (!$result) return null;
        return $field ? $result->$field : $result;
    }

    public function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
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