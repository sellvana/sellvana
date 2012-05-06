<?php

class FCom_Catalog_Model_CategoryProduct extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_category_product';

    public static function install()
    {
        $catProdTable = static::table();
        $productTable = FCom_Catalog_Model_Product::table();
        $categoryTable = FCom_Catalog_Model_Category::table();

        BDb::run("
CREATE TABLE IF NOT EXISTS `{$catProdTable}` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT(10) UNSIGNED NOT NULL,
  `category_id` INT(10) UNSIGNED NOT NULL,
  `sort_order` INT(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`,`category_id`),
  KEY `category_id__product_id` (`category_id`,`product_id`),
  KEY `category_id__sort_order` (`category_id`,`sort_order`),
  CONSTRAINT `FK_{$catProdTable}_category` FOREIGN KEY (`category_id`) REFERENCES `{$categoryTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_{$catProdTable}_product` FOREIGN KEY (`product_id`) REFERENCES `{$productTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=utf8;
        ");
    }
}