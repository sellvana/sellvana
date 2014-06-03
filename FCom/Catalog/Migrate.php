<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Catalog_Migrate extends BClass
{
    public function install__0_2_26()
    {
        $tProduct = FCom_Catalog_Model_Product::table();

        $tMedia = FCom_Catalog_Model_ProductMedia::table();
        $tMediaLibrary = FCom_Core_Model_MediaLibrary::table();

        $tProductLink = FCom_Catalog_Model_ProductLink::table();

        $tCategory = FCom_Catalog_Model_Category::table();
        $tCategoryProduct = FCom_Catalog_Model_CategoryProduct::table();

        $tSearchHistory = FCom_Catalog_Model_SearchHistory::table();
        $tSearchAlias = FCom_Catalog_Model_SearchAlias::table();

        BDb::ddlTableDef($tProduct, [
            'COLUMNS' => [
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
                'create_at'     => 'DATETIME DEFAULT NULL',
                'update_at'     => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                'data_serialized' => 'mediumtext null',
                'is_featured' => 'tinyint',
                'is_popular' => 'tinyint',
                'position' => 'SMALLINT(6) UNSIGNED DEFAULT NULL',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_local_sku' => 'UNIQUE (local_sku)',
                'UNQ_url_key'   => 'UNIQUE (url_key)',
//                'UNQ_product_name' => 'UNIQUE (product_name)',
                'is_hidden'     => '(is_hidden)',
                'IDX_featured' => '(is_featured)',
                'IDX_popular' => '(is_popular)',
            ],
        ]);

        BDb::ddlTableDef($tMedia, [
            'COLUMNS' => [
                'id'            => 'int unsigned NOT NULL AUTO_INCREMENT',
                'product_id'    => 'int(10) unsigned DEFAULT NULL',
                'media_type'    => 'char(1) NOT NULL',
                'file_id'       => 'int(11) unsigned NULL',
                'file_path'     => 'text',
                'remote_url'    => 'text',
                'data_serialized'     => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
                'label' => 'text',
                'position' => 'smallint',
                'main_thumb' => 'tinyint(1) unsigned default 0',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'file_id'        => '(file_id)',
                'product_id__media_type' => '(product_id, media_type)',

            ],
            'CONSTRAINTS' => [
                "FK_{$tMedia}_product" => "FOREIGN KEY (`product_id`) REFERENCES `{$tProduct}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tMedia}_file" => "FOREIGN KEY (`file_id`) REFERENCES `{$tMediaLibrary}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        BDb::ddlTableDef($tProductLink, [
            'COLUMNS' => [
                'id'            => 'int unsigned NOT NULL AUTO_INCREMENT',
                'link_type'     => "varchar(20) NOT NULL",
                'product_id'    => 'int(10) unsigned NOT NULL',
                'linked_product_id' => 'int(10) unsigned NOT NULL',
                'position' => 'smallint(6) null',
            ],
            'PRIMARY' => '(id)',
        ]);

        BDb::ddlTableDef($tCategory, [
            'COLUMNS' => [
                'id'            => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                'parent_id'     => 'INT(10) UNSIGNED DEFAULT NULL',
                'id_path'       => 'VARCHAR(50)  NULL',
                'level'         => 'tinyint',
                'sort_order'    => 'INT(10) UNSIGNED NULL',
                'node_name'     => 'VARCHAR(255) NULL',
                'full_name'     => 'VARCHAR(255) NULL',
                'url_key'       => 'VARCHAR(255) NULL',
                'url_path'      => 'VARCHAR(255) NULL',
                'num_children'  => 'INT(11) UNSIGNED DEFAULT NULL',
                'num_descendants' => 'INT(11) UNSIGNED DEFAULT NULL',
                'num_products'  => 'INT(10) UNSIGNED DEFAULT NULL',
                'is_enabled' => 'TINYINT(1) UNSIGNED DEFAULT 1 ',
                'is_virtual'    => 'TINYINT(3) UNSIGNED DEFAULT NULL',
                'is_top_menu'   => 'TINYINT(3) UNSIGNED DEFAULT NULL',
                'is_featured'   => 'TINYINT(3) UNSIGNED DEFAULT NULL',
                'data_serialized' => 'mediumtext null',
                'show_content'  => 'TINYINT(1) UNSIGNED DEFAULT NULL',
                'content'       => 'TEXT',
                'show_products' => 'TINYINT(1) UNSIGNED DEFAULT NULL',
                'show_sub_cat'  => 'TINYINT(1) UNSIGNED DEFAULT NULL',
                'layout_update' => 'TEXT',
                'page_title' => 'VARCHAR(255) DEFAULT NULL',
                'description'  => 'TEXT DEFAULT NULL',
                'meta_description' => 'TEXT DEFAULT NULL',
                'meta_keywords' => 'TEXT DEFAULT NULL',
                'show_sidebar' => 'TINYINT(1) UNSIGNED DEFAULT NULL',
                'show_view' => 'tinyint(1) unsigned default 0',
                'view_name' => 'varchar(255)',
                'page_parts' => 'varchar(50)',
                'image_url' => 'TEXT NULL',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'id_path'       => 'UNIQUE (`id_path`, `level`)',
                'full_name'     => 'UNIQUE (`full_name`)',
                'parent_id'     => 'UNIQUE (`parent_id`,`node_name`)',
                'is_top_menu'   => '(is_top_menu)',
                'IDX_featured'  => '(is_featured)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tCategory}_parent" => "FOREIGN KEY (`parent_id`) REFERENCES `{$tCategory}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        BDb::ddlTableDef($tCategoryProduct, [
            'COLUMNS' => [
                'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                'product_id'    => 'INT(10) UNSIGNED NOT NULL',
                'category_id'   => 'INT(10) UNSIGNED NOT NULL',
                'sort_order'    => 'INT(10) UNSIGNED DEFAULT NULL',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'product_id' => 'UNIQUE (`product_id`,`category_id`)',
                'category_id__product_id' => '(`category_id`,`product_id`)',
                'category_id__sort_order' => '(`category_id`,`sort_order`)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tCategoryProduct}_category" => "FOREIGN KEY (`category_id`) REFERENCES `{$tCategory}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tCategoryProduct}_product" => "FOREIGN KEY (`product_id`) REFERENCES `{$tProduct}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        BDb::run("REPLACE INTO {$tCategory} (id,id_path) VALUES (1,1)");


        BDb::ddlTableDef($tSearchHistory, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'term_type' => "char(1) not null default 'F'", // (F)ull or (W)ord
                'query' => 'varchar(50) not null',
                'first_at' => 'datetime not null',
                'last_at' => 'datetime not null',
                'num_searches' => 'int not null default 0',
                'num_products_found_last' => 'int not null default 0',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_query' => 'UNIQUE (term_type, query)',
            ],
        ]);

        BDb::ddlTableDef($tSearchAlias, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'alias_type' => "char(1) not null default 'F'", // (F)ull or (W)ord
                'alias_term' => 'varchar(50) not null',
                'target_term' => 'varchar(50) not null',
                'num_hits' => 'int not null default 0',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_alias' => 'UNIQUE (alias_type, alias_term)',
                'IDX_target' => '(target_term)',
            ],
        ]);
        FCom_Catalog_Model_Category::i()->update_many(['show_products' => 1, 'show_sidebar' => 1, 'is_enabled' => 1]);
    }

    public function upgrade__0_2_1__0_2_2()
    {
        BDb::ddlTableDef(FCom_Catalog_Model_Product::table(), [
            'COLUMNS' => [
                'unique_id'     => 'RENAME local_sku varchar(100) not null',
                'disabled'      => 'RENAME is_hidden tinyint not null default 0',
                'image_url'     => 'RENAME thumb_url text',
                'images_data'   => 'text',
                'markup'        => 'decimal(12,2) null default null',
            ],
        ]);
    }

    public function upgrade__0_2_2__0_2_3()
    {
        BDb::ddlTableDef(FCom_Catalog_Model_Product::table(), [
            'COLUMNS' => [
                'images_data' => 'DROP',
                'data_serialized' => 'mediumtext null',
            ],
        ]);
        BDb::ddlTableDef(FCom_Catalog_Model_Category::table(), [
            'COLUMNS' => [
                'data_serialized' => 'mediumtext null',
            ],
        ]);
    }

    public function upgrade__0_2_3__0_2_4()
    {
        $table = FCom_Catalog_Model_Product::table();
        BDb::ddlTableDef($table, [
            'COLUMNS' => [
                  'create_dt'      => 'RENAME create_at DATETIME DEFAULT NULL',
                  'update_dt'      => 'RENAME update_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ],
        ]);
    }

    public function upgrade__0_2_4__0_2_5()
    {
        BDb::ddlTableDef(FCom_Catalog_Model_Category::table(), [
            'COLUMNS' => [
                'level' => 'tinyint null after id_path',
            ],
            'KEYS' => [
                'id_path' => 'UNIQUE (`id_path`, `level`)',
            ],
        ]);
    }

    public function upgrade__0_2_5__0_2_6()
    {
        $tMedia = FCom_Catalog_Model_ProductMedia::table();
        BDb::ddlTableDef($tMedia, [
            'COLUMNS' => [
                'file_id'       => 'int(11) unsigned NULL',
                'file_path'     => 'text',
                'remote_url'    => 'text',
            ],
        ]);
    }

    public function upgrade__0_2_6__0_2_7()
    {
        $tSearchHistory = FCom_Catalog_Model_SearchHistory::table();
        $tSearchAlias = FCom_Catalog_Model_SearchAlias::table();

        BDb::ddlTableDef($tSearchHistory, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'term_type' => "char(1) not null default 'F'", // (F)ull or (W)ord
                'query' => 'varchar(50) not null',
                'first_at' => 'datetime not null',
                'last_at' => 'datetime not null',
                'num_searches' => 'int not null default 0',
                'num_products_found_last' => 'int not null default 0',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_query' => 'UNIQUE (term_type, query)',
            ],
        ]);

        BDb::ddlTableDef($tSearchAlias, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'alias_type' => "char(1) not null default 'F'", // (F)ull or (W)ord
                'alias_term' => 'varchar(50) not null',
                'target_term' => 'varchar(50) not null',
                'num_hits' => 'int not null default 0',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_alias' => 'UNIQUE (alias_type, alias_term)',
                'IDX_target' => '(target_term)',
            ],
        ]);
    }

    public function upgrade__0_2_7__0_2_8()
    {
        $tMedia = FCom_Catalog_Model_ProductMedia::table();
        BDb::ddlTableDef($tMedia, [
            'COLUMNS' => [
                'data_serialized'     => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
        ]);
    }

    public function upgrade__0_2_8__0_2_9()
    {
        $tMedia = FCom_Catalog_Model_ProductMedia::table();
        BDb::ddlTableDef($tMedia, [
            'COLUMNS' => [
                'label' => 'text',
                'position' => 'smallint',
            ],
        ]);
    }

    public function upgrade__0_2_9__0_2_10()
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::ddlTableDef($tProduct, [
            'COLUMNS' => [
                'is_featured' => 'tinyint',
                'is_popular' => 'tinyint',
            ],
            'KEYS' => [
                'IDX_featured' => '(is_featured)',
                'IDX_popular' => '(is_popular)',
            ],
        ]);
    }

    public function upgrade__0_2_10__0_2_11()
    {
        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::ddlTableDef($tCategory, [
            'COLUMNS' => [
                'show_content'  => 'TINYINT(1) UNSIGNED DEFAULT NULL',
                'content'       => 'TEXT',
                'show_products' => 'TINYINT(1) UNSIGNED DEFAULT NULL',
                'show_sub_cat'  => 'TINYINT(1) UNSIGNED DEFAULT NULL',
                'layout_update' => 'TEXT',
        ]]);
    }

    public function upgrade__0_2_11__0_2_12()
    {
        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::ddlTableDef($tCategory, [
                'COLUMNS' => [
                    'page_title' => 'VARCHAR(255) DEFAULT NULL',
                    'description'  => 'TEXT DEFAULT NULL',
                    'meta_description' => 'TEXT DEFAULT NULL',
                    'meta_keywords' => 'TEXT DEFAULT NULL',
                ]]);
    }

    public function upgrade__0_2_12__0_2_13()
    {
        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::ddlTableDef($tCategory, [
                'COLUMNS' => [
                    'show_sidebar' => 'TINYINT(1) UNSIGNED DEFAULT NULL'
                ]]);
    }

    public function upgrade__0_2_13__0_2_14()
    {
        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::ddlTableDef($tCategory, [
            'COLUMNS' => [
                'is_enabled' => 'TINYINT(1) UNSIGNED DEFAULT 1 AFTER num_products',
            ],
            //TODO: figure out which keys are needed
        ]);
        FCom_Catalog_Model_Category::i()->update_many(['show_products' => 1, 'show_sidebar' => 1, 'is_enabled' => 1]);
    }

    public function upgrade__0_2_14__0_2_15()
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::ddlTableDef($tProduct, [
            'COLUMNS' => [
                'position' => 'SMALLINT(6) UNSIGNED DEFAULT NULL'
            ]
        ]);
    }

    public function upgrade__0_2_15__0_2_16()
    {
        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::ddlTableDef($tCategory, [
            'COLUMNS' => [
                'show_view' => 'tinyint(1) unsigned default 0',
                'view_name' => 'varchar(255)',
                'page_parts' => 'varchar(50)',
            ],
        ]);
    }

    public function upgrade__0_2_16__0_2_17()
    {
        $tMedia = FCom_Catalog_Model_ProductMedia::table();
        BDb::ddlTableDef($tMedia, [
            'COLUMNS' => [
                'main_thumb' => 'tinyint(1) unsigned default 0',
            ],
        ]);
    }

    public function upgrade__0_2_17__0_2_18()
    {
        /*
        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::ddlTableDef($tCategory, array(
            'COLUMNS' => array(
                'show_cms_page' => 'tinyint(1) unsigned default null',
                'cms_page' => 'text default null',
            ),
        ));
        */
    }

    public function upgrade__0_2_18__0_2_19()
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::ddlTableDef($tProduct, [
            'KEYS' => [
                'UNQ_product_name' => 'DROP',
            ],
        ]);
    }

    public function upgrade__0_2_19__0_2_20()
    {
        $tProductLink = FCom_Catalog_Model_ProductLink::table();
        BDb::ddlTableDef($tProductLink, [
            'COLUMNS' => [
                'link_type'     => "enum('related','similar', 'cross-sell') NOT NULL",
            ],
        ]);
    }

    public function upgrade__0_2_20__0_2_21()
    {
        /*
        $tProductLink = FCom_Catalog_Model_ProductLink::table();
        BDb::ddlTableDef($tProductLink, array(
            'COLUMNS' => array(
                'link_type'     => "enum('related','similar', 'cross_sell') NOT NULL",
            ),
        ));
        */
    }

    public function upgrade__0_2_21__0_2_22()
    {
        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::ddlTableDef($tCategory, [
            'COLUMNS' => [
                'id_path' => 'varchar(50) null',
            ],
        ]);
    }

    public function upgrade__0_2_22__0_2_23()
    {
        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::ddlTableDef($tCategory, [
            'COLUMNS' => [
                'image_url' => 'TEXT null',
            ],
        ]);
    }

    public function upgrade__0_2_23__0_2_24()
    {
        $tProductLink = FCom_Catalog_Model_ProductLink::table();
        BDb::ddlTableDef($tProductLink, [
            'COLUMNS' => [
                'position' => 'smallint(6) null',
            ],
        ]);
    }

    public function upgrade__0_2_24__0_2_25()
    {
        $tProductLink = FCom_Catalog_Model_ProductLink::table();
        BDb::ddlTableDef($tProductLink, [
            'COLUMNS' => [
                'link_type' => "varchar(20) NOT NULL",
            ],
        ]);
    }

    public function upgrade__0_2_25__0_2_26()
    {
        $tCategory = FCom_Catalog_Model_Category::table();
        BDb::ddlTableDef($tCategory, [
            'COLUMNS' => [
                'is_featured'   => 'TINYINT(3) UNSIGNED DEFAULT NULL',
            ],
            'KEYS' => [
                'IDX_featured'  => '(is_featured)',
            ],
        ]);
    }
}
