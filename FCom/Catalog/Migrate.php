<?php

class FCom_Catalog_Migrate extends BClass
{
    public function run()
    {
        BDb::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        $productTable = FCom_Catalog_Model_Product::table();
        $categoryTable = FCom_Catalog_Model_Category::table();
        $catProdTable = FCom_Catalog_Model_CategoryProduct::table();

        BDb::run("

CREATE TABLE IF NOT EXISTS `{$productTable}` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(10) UNSIGNED DEFAULT NULL,
  `entity_id` INT(10) UNSIGNED DEFAULT NULL,
  `manuf_id` INT(10) UNSIGNED DEFAULT NULL,
  `manuf_vendor_id` INT(10) UNSIGNED DEFAULT NULL,
  `manuf_sku` VARCHAR(100) NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `url_key` VARCHAR(255) DEFAULT NULL,
  `base_price` DECIMAL(12,4) NOT NULL,
  `notes` TEXT,
  `uom` VARCHAR(10) NOT NULL DEFAULT 'EACH',
  `create_dt` DATETIME DEFAULT NULL,
  `update_dt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `image_url` TEXT,
  `calc_uom` VARCHAR(15) DEFAULT NULL,
  `calc_qty` DECIMAL(12,4) UNSIGNED DEFAULT NULL,
  `base_uom` VARCHAR(15) DEFAULT NULL,
  `base_qty` INT(10) UNSIGNED DEFAULT NULL,
  `pack_uom` VARCHAR(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url_key` (`url_key`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$categoryTable}` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT(10) UNSIGNED DEFAULT NULL,
  `id_path` VARCHAR(50) NOT NULL,
  `sort_order` INT(10) UNSIGNED NOT NULL,
  `node_name` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `url_key` VARCHAR(255) NOT NULL,
  `url_path` VARCHAR(255) NOT NULL,
  `num_children` INT(11) UNSIGNED DEFAULT NULL,
  `num_descendants` INT(11) UNSIGNED DEFAULT NULL,
  `num_products` INT(10) UNSIGNED DEFAULT NULL,
  `is_virtual` TINYINT(3) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_path` (`id_path`),
  UNIQUE KEY `full_name` (`full_name`),
  UNIQUE KEY `parent_id` (`parent_id`,`node_name`),
  KEY `parent_id_2` (`parent_id`,`sort_order`),
  CONSTRAINT `FK_{$categoryTable}_parent` FOREIGN KEY (`parent_id`) REFERENCES `{$categoryTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$catProdTable}` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT(10) UNSIGNED NOT NULL,
  `category_id` INT(10) UNSIGNED NOT NULL,
  `sort_order` INT(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`,`category_id`),
  KEY `NewIndex1` (`category_id`,`product_id`),
  KEY `NewIndex2` (`category_id`,`sort_order`),
  CONSTRAINT `FK_{$catProdTable}_category` FOREIGN KEY (`category_id`) REFERENCES `{$categoryTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_{$catProdTable}_product` FOREIGN KEY (`product_id`) REFERENCES `{$productTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=utf8;

         ");
    }
}