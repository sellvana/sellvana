<?php

class FCom_Catalog_Migrate extends BClass
{
    public function upgrade__0_2_1__0_2_2()
    {
        BDb::ddlTableDef(FCom_Catalog_Model_Product::table(), array(
            'COLUMNS' => array(
                'unique_id'     => 'RENAME local_sku varchar(100) not null',
                'disabled'      => 'RENAME is_hidden tinyint not null default 0',
                'image_url'     => 'RENAME thumb_url text',
                'images_data'   => 'text',
                'markup'        => 'decimal(12,2) null default null',
            ),
        ));
    }

    public function install__0_2_2()
    {
        $tProduct = FCom_Catalog_Model_Product::table();

        $tMedia = FCom_Catalog_Model_ProductMedia::table();
        $tMediaLibrary = FCom_Core_Model_MediaLibrary::table();

        $tProductLink = FCom_Catalog_Model_ProductLink::table();

        $tCategory = FCom_Catalog_Model_Category::table();
        $tCategoryProduct = FCom_Catalog_Model_CategoryProduct::table();

        BDb::ddlTableDef($tProduct, array(
            'COLUMNS' => array(
                'id'            => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                'local_sku'     => 'VARCHAR(100) NOT NULL',
                'product_name'  => 'VARCHAR(255) NOT NULL',
                'short_description' => 'TEXT',
                'description'   => 'TEXT',
                'url_key'       => 'VARCHAR(255) DEFAULT NULL',
                'cost'          => 'decimal(12,2) null default null',
                'msrp'          => 'decimal(12,2) null default null',
                'map'           => 'decimal(12,2) null default null',
                'markup'        => 'decimal(12,2) null default null',
                'base_price'    => 'DECIMAL(12,2) NOT NULL',
                'sale_price'    => 'decimal(12,2) null default null',
                'net_weight'    => 'decimal(12,2) null default null',
                'ship_weight'   => 'decimal(12,2) null default null',
                'is_hidden'     => 'tinyint(1) not null default 0',
                'notes'         => 'TEXT',
                'uom'           => "VARCHAR(10) NOT NULL DEFAULT 'EACH'",
                'thumb_url'     => 'TEXT',
                'images_data'   => 'TEXT',
                'create_dt'     => 'DATETIME DEFAULT NULL',
                'update_dt'     => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_local_sku' => 'UNIQUE (local_sku)',
                'UNQ_url_key'   => 'UNIQUE (url_key)',
                'UNQ_product_name' => 'UNIQUE (product_name)',
                'is_hidden'     => '(is_hidden)',
            ),
        ));

        BDb::ddlTableDef($tMedia, array(
            'COLUMNS' => array(
                'id'            => 'unsigned NOT NULL AUTO_INCREMENT',
                'product_id'    => 'int(10) unsigned DEFAULT NULL',
                'media_type'    => 'char(1) NOT NULL',
                'file_id'       => 'int(11) unsigned NOT NULL',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'file_id'        => '(file_id)',
                'product_id__media_type' => '(product_id, media_type)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tMedia}_product" => "FOREIGN KEY (`product_id`) REFERENCES `{$tProduct}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tMedia}_file" => "FOREIGN KEY (`file_id`) REFERENCES `{$tMediaLibrary}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ),
        ));

        BDb::ddlTableDef($tProductLink, array(
            'COLUMNS' => array(
                'id'            => 'unsigned NOT NULL AUTO_INCREMENT',
                'link_type'     => "enum('related','similar') NOT NULL",
                'product_id'    => 'int(10) unsigned NOT NULL',
                'linked_product_id' => 'int(10) unsigned NOT NULL',
            ),
            'PRIMARY' => '(id)',
        ));

        BDb::ddlTableDef($tCategory, array(
            'COLUMNS' => array(
                'id'            => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT', 
                'parent_id'     => 'INT(10) UNSIGNED DEFAULT NULL', 
                'id_path'       => 'VARCHAR(50) NOT NULL', 
                'sort_order'    => 'INT(10) UNSIGNED NOT NULL', 
                'node_name'     => 'VARCHAR(255) NOT NULL', 
                'full_name'     => 'VARCHAR(255) NOT NULL', 
                'url_key'       => 'VARCHAR(255) NOT NULL', 
                'url_path'      => 'VARCHAR(255) NOT NULL', 
                'num_children'  => 'INT(11) UNSIGNED DEFAULT NULL', 
                'num_descendants' => 'INT(11) UNSIGNED DEFAULT NULL', 
                'num_products'  => 'INT(10) UNSIGNED DEFAULT NULL', 
                'is_virtual'    => 'TINYINT(3) UNSIGNED DEFAULT NULL', 
                'is_top_menu'   => 'TINYINT(3) UNSIGNED DEFAULT NULL', 
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'id_path'       => 'UNIQUE (`id_path`)',
                'full_name'     => 'UNIQUE (`full_name`)',
                'parent_id'     => 'UNIQUE (`parent_id`,`node_name`)',
                'is_top_menu'   => '(is_top_menu)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tCategory}_parent" => "FOREIGN KEY (`parent_id`) REFERENCES `{$tCategory}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ),
        ));

        BDb::ddlTableDef($tCategoryProduct, array(
            'COLUMNS' => array(
                'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT', 
                'product_id'    => 'INT(10) UNSIGNED NOT NULL', 
                'category_id'   => 'INT(10) UNSIGNED NOT NULL', 
                'sort_order'    => 'INT(10) UNSIGNED DEFAULT NULL', 
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'product_id' => 'UNIQUE (`product_id`,`category_id`)',
                'category_id__product_id' => '(`category_id`,`product_id`)',
                'category_id__sort_order' => '(`category_id`,`sort_order`)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tCategoryProduct}_category" => "FOREIGN KEY (`category_id`) REFERENCES `{$tCategory}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tCategoryProduct}_product" => "FOREIGN KEY (`product_id`) REFERENCES `{$tProduct}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ),
        ));

        BDb::run("REPLACE INTO {$tCategory} (id,id_path) VALUES (1,1)");
    }
}