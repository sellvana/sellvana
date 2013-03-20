<?php

class FCom_Catalog_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.1', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.2', array($this, 'upgrade_0_1_2'));
        BMigrate::upgrade('0.1.2', '0.1.3', array($this, 'upgrade_0_1_3'));
        BMigrate::upgrade('0.1.3', '0.1.4', array($this, 'upgrade_0_1_4'));
        BMigrate::upgrade('0.1.4', '0.1.5', array($this, 'upgrade_0_1_5'));
        BMigrate::upgrade('0.1.5', '0.1.6', array($this, 'upgrade_0_1_6'));
        BMigrate::upgrade('0.1.6', '0.1.7', array($this, 'upgrade_0_1_7'));
        BMigrate::upgrade('0.1.7', '0.1.8', array($this, 'upgrade_0_1_8'));
        BMigrate::upgrade('0.1.8', '0.1.9', array($this, 'upgrade_0_1_9'));
        BMigrate::upgrade('0.1.9', '0.2.0', array($this, 'upgrade_0_2_0'));
    }

    public function install()
    {
        $tFamily = FCom_Catalog_Model_Family::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tFamily} (
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

        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tProduct} (
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
        ");

        $tMedia = FCom_Catalog_Model_ProductMedia::table();
        $tProduct = FCom_Catalog_Model_Product::table();
        $tMediaLibrary = FCom_Core_Model_MediaLibrary::table();

        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tMedia} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `product_id` int(10) unsigned DEFAULT NULL,
            `media_type` char(1) NOT NULL,
            `file_id` int(11) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            KEY `file_id` (`file_id`),
            KEY `product_id__media_type` (`product_id`,`media_type`),
            CONSTRAINT `FK_{$tMedia}_product` FOREIGN KEY (`product_id`) REFERENCES `{$tProduct}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `FK_{$tMedia}_file` FOREIGN KEY (`file_id`) REFERENCES `{$tMediaLibrary}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        ");

        $tProductLink = FCom_Catalog_Model_ProductLink::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tProductLink} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `link_type` enum('related','similar') NOT NULL,
            `product_id` int(10) unsigned NOT NULL,
            `linked_product_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tProductFamily = FCom_Catalog_Model_ProductFamily::table();
        $tFamily = FCom_Catalog_Model_Family::table();
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tProductFamily} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `product_id` int(10) unsigned NOT NULL,
            `family_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `family_id__product_id` (`family_id`,`product_id`),
            KEY `product_id` (`product_id`),
            CONSTRAINT `FK_{$tProductFamily}_family` FOREIGN KEY (`family_id`) REFERENCES `{$tFamily}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `FK_{$tProductFamily}_product` FOREIGN KEY (`product_id`) REFERENCES `{$tProduct}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tCategory} (
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
            CONSTRAINT `FK_{$tCategory}_parent` FOREIGN KEY (`parent_id`) REFERENCES `{$tCategory}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=INNODB DEFAULT CHARSET=utf8;
        ");

        $tCategoryProduct = FCom_Catalog_Model_CategoryProduct::table();
        $tProduct = FCom_Catalog_Model_Product::table();
        $tCategory = FCom_Catalog_Model_Category::table();

        BDb::run("
            CREATE TABLE IF NOT EXISTS `{$tCategoryProduct}` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `product_id` INT(10) UNSIGNED NOT NULL,
            `category_id` INT(10) UNSIGNED NOT NULL,
            `sort_order` INT(10) UNSIGNED DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `product_id` (`product_id`,`category_id`),
            KEY `category_id__product_id` (`category_id`,`product_id`),
            KEY `category_id__sort_order` (`category_id`,`sort_order`),
            CONSTRAINT `FK_{$tCategoryProduct}_category` FOREIGN KEY (`category_id`) REFERENCES `{$tCategory}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `FK_{$tCategoryProduct}_product` FOREIGN KEY (`product_id`) REFERENCES `{$tProduct}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=INNODB DEFAULT CHARSET=utf8;
        ");

        $tProductField = FCom_CustomField_Model_ProductField::table();
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tProductField} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `product_id` int(10) unsigned NOT NULL,
            `_fieldset_ids` text,
            `_add_field_ids` text,
            `_hide_field_ids` text,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tProductField}_product` FOREIGN KEY (`product_id`) REFERENCES {$tProduct} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        BDb::run("REPLACE INTO {$tCategory} (id,id_path) VALUES (1,1)");
    }

    public function upgrade_0_1_2()
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tProduct, 'weight')) {
            return;
        }
        BDb::run("
            ALTER TABLE ".$tProduct." ADD `weight` DECIMAL( 10, 4 ) NOT NULL
        ");
    }

    public function upgrade_0_1_3()
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tProduct, 'short_description')) {
            return;
        }
        BDb::run("
            ALTER TABLE ".$tProduct." ADD `short_description` TEXT after product_name
        ");
    }

    public function upgrade_0_1_4()
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tProduct, 'uniq_id')) {
            return;
        }
        BDb::run("
            ALTER TABLE ".$tProduct." ADD `uniq_id` varchar(255) NOT NULL default '' after id
        ");
    }

    public function upgrade_0_1_5()
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::run("
            UPDATE ".$tProduct." SET uniq_id = id ;
        ");


        BDb::ddlTableColumns($tProduct, null, array('uniq_id'=>'(`uniq_id`)'));
    }

    public function upgrade_0_1_6()
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::run("
            ALTER TABLE ".$tProduct." CHANGE `uniq_id` `unique_id` varchar(255) NOT NULL default '' after id
        ");
    }

    public function upgrade_0_1_7()
    {
        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::run("
            ALTER TABLE ".$tCategory." ADD `top_menu` tinyint(1) NOT NULL default 0
        ");
    }

    public function upgrade_0_1_8()
    {
        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::ddlTableColumns($tCategory, null, array('top_menu'=>'(`top_menu`)'));
    }

    public function upgrade_0_1_9()
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::run("
            ALTER TABLE ".$tProduct." ADD `disabled` tinyint(1) NOT NULL default 0,
                ADD INDEX (disabled)
        ");
    }

    public function upgrade_0_2_0()
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::run("
            ALTER TABLE ".$tProduct." ADD INDEX (product_name)
        ");
    }
}