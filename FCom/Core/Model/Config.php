<?php

class FCom_Core_Model_Config extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_core_config';
    protected $_instance_id_column = 'path';

    public static function fetch($path)
    {
        return ($row = static::load($path)) ? $row->value : null;
    }

    public static function store($path, $value)
    {
        if (($row = static::load($path))) {
            $row->set('value', $value)->save();
        } else {
            static::create(array('path'=>$path, 'value'=>$value))->save();
        }
    }

    public static function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `path` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }
}