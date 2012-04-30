<?php

class FCom_Catalog_Model_Family extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_family';

    public static function install()
    {
        $t = static::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$t} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `family_name` varchar(100) NOT NULL,
  `manuf_vendor_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `family_name` (`family_name`)
  /*,
  KEY `FK_a_family_manuf` (`manuf_vendor_id`),
  CONSTRAINT `FK_a_family_manuf` FOREIGN KEY (`manuf_vendor_id`) REFERENCES `a_vendor` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
  */
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        ");
    }
}