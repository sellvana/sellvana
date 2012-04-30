<?php

class FCom_Catalog_Model_ProductFamily extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_family';
    protected static $_origClass = __CLASS__;

    public static function install()
    {
        $t = static::table();
        $familyTable = FCom_Catalog_Model_Family::table();
        $prodTable = FCom_Catalog_Model_Product::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$t} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `family_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `family_id__product_id` (`family_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `FK_{$t}_family` FOREIGN KEY (`family_id`) REFERENCES `{$familyTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_{$t}_product` FOREIGN KEY (`product_id`) REFERENCES `{$prodTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}