<?php

class FCom_Catalog_Model_ProductLink extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_link';
    protected static $_origClass = __CLASS__;

    public static function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `link_type` enum('related','similar') NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `linked_product_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}