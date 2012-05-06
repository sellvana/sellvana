<?php

class FCom_CustomField_Model_Set extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_fieldset';

    public static function install()
    {
        $tSet = static::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tSet} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `set_type` enum('product') NOT NULL DEFAULT 'product',
  `set_code` varchar(100) NOT NULL,
  `set_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}
