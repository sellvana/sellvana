<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Catalog_Migrate
 *
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Catalog_Model_ProductMedia $FCom_Catalog_Model_ProductMedia
 * @property FCom_Core_Model_MediaLibrary $FCom_Core_Model_MediaLibrary
 * @property FCom_Catalog_Model_ProductLink $FCom_Catalog_Model_ProductLink
 * @property FCom_Catalog_Model_Category $FCom_Catalog_Model_Category
 * @property FCom_Catalog_Model_CategoryProduct $FCom_Catalog_Model_CategoryProduct
 * @property FCom_Catalog_Model_SearchHistory $FCom_Catalog_Model_SearchHistory
 * @property FCom_Catalog_Model_SearchAlias $FCom_Catalog_Model_SearchAlias
 * @property FCom_Catalog_Model_ProductHistory $FCom_Catalog_Model_ProductHistory
 * @property FCom_Catalog_Model_InventoryBin $FCom_Catalog_Model_InventoryBin
 * @property FCom_Catalog_Model_InventorySku $FCom_Catalog_Model_InventorySku
 * @property FCom_Catalog_Model_InventorySkuHistory $FCom_Catalog_Model_InventorySkuHistory
 */
class FCom_Catalog_Migrate extends BClass
{
    public function install__0_2_27()
    {
        $tProduct = $this->FCom_Catalog_Model_Product->table();

        $tMedia = $this->FCom_Catalog_Model_ProductMedia->table();
        $tMediaLibrary = $this->FCom_Core_Model_MediaLibrary->table();

        $tProductLink = $this->FCom_Catalog_Model_ProductLink->table();

        $tCategory = $this->FCom_Catalog_Model_Category->table();
        $tCategoryProduct = $this->FCom_Catalog_Model_CategoryProduct->table();

        $tSearchHistory = $this->FCom_Catalog_Model_SearchHistory->table();
        $tSearchAlias = $this->FCom_Catalog_Model_SearchAlias->table();

        $this->BDb->ddlTableDef($tProduct, [
            BDb::COLUMNS => [
                'id'            => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                'product_sku'     => 'VARCHAR(100) NOT NULL',
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
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_product_sku' => 'UNIQUE (product_sku)',
                'UNQ_url_key'   => 'UNIQUE (url_key)',
//                'UNQ_product_name' => 'UNIQUE (product_name)',
                'is_hidden'     => '(is_hidden)',
                'IDX_featured' => '(is_featured)',
                'IDX_popular' => '(is_popular)',
            ],
        ]);

        $this->BDb->ddlTableDef($tMedia, [
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'file_id'        => '(file_id)',
                'product_id__media_type' => '(product_id, media_type)',

            ],
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct],
                'file' => ['file_id', $tMediaLibrary],
            ],
        ]);

        $this->BDb->ddlTableDef($tProductLink, [
            BDb::COLUMNS => [
                'id'            => 'int unsigned NOT NULL AUTO_INCREMENT',
                'link_type'     => "varchar(20) NOT NULL",
                'product_id'    => 'int(10) unsigned NOT NULL',
                'linked_product_id' => 'int(10) unsigned NOT NULL',
                'position' => 'smallint(6) null',
            ],
            BDb::PRIMARY => '(id)',
        ]);

        $this->BDb->ddlTableDef($tCategory, [
            BDb::COLUMNS => [
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
                'featured_image_url'   => 'TEXT NULL',
                'nav_callout_image_url'   => 'TEXT NULL',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'id_path'       => 'UNIQUE (`id_path`, `level`)',
                'full_name'     => 'UNIQUE (`full_name`)',
                'parent_id'     => 'UNIQUE (`parent_id`,`node_name`)',
                'is_top_menu'   => '(is_top_menu)',
                'IDX_featured'  => '(is_featured)',
            ],
            BDb::CONSTRAINTS => [
                'parent' => ['parent_id', $tCategory],
            ],
        ]);

        $this->BDb->ddlTableDef($tCategoryProduct, [
            BDb::COLUMNS => [
                'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                'product_id'    => 'INT(10) UNSIGNED NOT NULL',
                'category_id'   => 'INT(10) UNSIGNED NOT NULL',
                'sort_order'    => 'INT(10) UNSIGNED DEFAULT NULL',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'product_id' => 'UNIQUE (`product_id`,`category_id`)',
                'category_id__product_id' => '(`category_id`,`product_id`)',
                'category_id__sort_order' => '(`category_id`,`sort_order`)',
            ],
            BDb::CONSTRAINTS => [
                'category' => ['category_id', $tCategory],
                'product' => ['product_id', $tProduct],
            ],
        ]);

        $this->BDb->run("REPLACE INTO {$tCategory} (id,id_path) VALUES (1,1)");


        $this->BDb->ddlTableDef($tSearchHistory, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'term_type' => "char(1) not null default 'F'", // (F)ull or (W)ord
                'query' => 'varchar(50) not null',
                'first_at' => 'datetime not null',
                'last_at' => 'datetime not null',
                'num_searches' => 'int not null default 0',
                'num_products_found_last' => 'int not null default 0',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_query' => 'UNIQUE (term_type, query)',
            ],
        ]);

        $this->BDb->ddlTableDef($tSearchAlias, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'alias_type' => "char(1) not null default 'F'", // (F)ull or (W)ord
                'alias_term' => 'varchar(50) not null',
                'target_term' => 'varchar(50) not null',
                'num_hits' => 'int not null default 0',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_alias' => 'UNIQUE (alias_type, alias_term)',
                'IDX_target' => '(target_term)',
            ],
        ]);
        $this->FCom_Catalog_Model_Category->update_many(['show_products' => 1, 'show_sidebar' => 1, 'is_enabled' => 1]);
    }

    public function upgrade__0_2_1__0_2_2()
    {
        $this->BDb->ddlTableDef($this->FCom_Catalog_Model_Product->table(), [
            BDb::COLUMNS => [
                'unique_id'     => 'RENAME product_sku varchar(100) not null',
                'disabled'      => 'RENAME is_hidden tinyint not null default 0',
                'image_url'     => 'RENAME thumb_url text',
                'images_data'   => 'text',
                'markup'        => 'decimal(12,2) null default null',
            ],
        ]);
    }

    public function upgrade__0_2_2__0_2_3()
    {
        $this->BDb->ddlTableDef($this->FCom_Catalog_Model_Product->table(), [
            BDb::COLUMNS => [
                'images_data' => BDb::DROP,
                'data_serialized' => 'mediumtext null',
            ],
        ]);
        $this->BDb->ddlTableDef($this->FCom_Catalog_Model_Category->table(), [
            BDb::COLUMNS => [
                'data_serialized' => 'mediumtext null',
            ],
        ]);
    }

    public function upgrade__0_2_3__0_2_4()
    {
        $table = $this->FCom_Catalog_Model_Product->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'create_dt'      => 'RENAME create_at DATETIME DEFAULT NULL',
                  'update_dt'      => 'RENAME update_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ],
        ]);
    }

    public function upgrade__0_2_4__0_2_5()
    {
        $this->BDb->ddlTableDef($this->FCom_Catalog_Model_Category->table(), [
            BDb::COLUMNS => [
                'level' => 'tinyint null after id_path',
            ],
            BDb::KEYS => [
                'id_path' => 'UNIQUE (`id_path`, `level`)',
            ],
        ]);
    }

    public function upgrade__0_2_5__0_2_6()
    {
        $tMedia = $this->FCom_Catalog_Model_ProductMedia->table();
        $this->BDb->ddlTableDef($tMedia, [
            BDb::COLUMNS => [
                'file_id'       => 'int(11) unsigned NULL',
                'file_path'     => 'text',
                'remote_url'    => 'text',
            ],
        ]);
    }

    public function upgrade__0_2_6__0_2_7()
    {
        $tSearchHistory = $this->FCom_Catalog_Model_SearchHistory->table();
        $tSearchAlias = $this->FCom_Catalog_Model_SearchAlias->table();

        $this->BDb->ddlTableDef($tSearchHistory, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'term_type' => "char(1) not null default 'F'", // (F)ull or (W)ord
                'query' => 'varchar(50) not null',
                'first_at' => 'datetime not null',
                'last_at' => 'datetime not null',
                'num_searches' => 'int not null default 0',
                'num_products_found_last' => 'int not null default 0',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_query' => 'UNIQUE (term_type, query)',
            ],
        ]);

        $this->BDb->ddlTableDef($tSearchAlias, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'alias_type' => "char(1) not null default 'F'", // (F)ull or (W)ord
                'alias_term' => 'varchar(50) not null',
                'target_term' => 'varchar(50) not null',
                'num_hits' => 'int not null default 0',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_alias' => 'UNIQUE (alias_type, alias_term)',
                'IDX_target' => '(target_term)',
            ],
        ]);
    }

    public function upgrade__0_2_7__0_2_8()
    {
        $tMedia = $this->FCom_Catalog_Model_ProductMedia->table();
        $this->BDb->ddlTableDef($tMedia, [
            BDb::COLUMNS => [
                'data_serialized'     => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
        ]);
    }

    public function upgrade__0_2_8__0_2_9()
    {
        $tMedia = $this->FCom_Catalog_Model_ProductMedia->table();
        $this->BDb->ddlTableDef($tMedia, [
            BDb::COLUMNS => [
                'label' => 'text',
                'position' => 'smallint',
            ],
        ]);
    }

    public function upgrade__0_2_9__0_2_10()
    {
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $this->BDb->ddlTableDef($tProduct, [
            BDb::COLUMNS => [
                'is_featured' => 'tinyint',
                'is_popular' => 'tinyint',
            ],
            BDb::KEYS => [
                'IDX_featured' => '(is_featured)',
                'IDX_popular' => '(is_popular)',
            ],
        ]);
    }

    public function upgrade__0_2_10__0_2_11()
    {
        $tCategory = $this->FCom_Catalog_Model_Category->table();
        $this->BDb->ddlTableDef($tCategory, [
            BDb::COLUMNS => [
                'show_content'  => 'TINYINT(1) UNSIGNED DEFAULT NULL',
                'content'       => 'TEXT',
                'show_products' => 'TINYINT(1) UNSIGNED DEFAULT NULL',
                'show_sub_cat'  => 'TINYINT(1) UNSIGNED DEFAULT NULL',
                'layout_update' => 'TEXT',
        ]]);
    }

    public function upgrade__0_2_11__0_2_12()
    {
        $tCategory = $this->FCom_Catalog_Model_Category->table();
        $this->BDb->ddlTableDef($tCategory, [
                BDb::COLUMNS => [
                    'page_title' => 'VARCHAR(255) DEFAULT NULL',
                    'description'  => 'TEXT DEFAULT NULL',
                    'meta_description' => 'TEXT DEFAULT NULL',
                    'meta_keywords' => 'TEXT DEFAULT NULL',
                ]]);
    }

    public function upgrade__0_2_12__0_2_13()
    {
        $tCategory = $this->FCom_Catalog_Model_Category->table();
        $this->BDb->ddlTableDef($tCategory, [
                BDb::COLUMNS => [
                    'show_sidebar' => 'TINYINT(1) UNSIGNED DEFAULT NULL'
                ]]);
    }

    public function upgrade__0_2_13__0_2_14()
    {
        $tCategory = $this->FCom_Catalog_Model_Category->table();
        $this->BDb->ddlTableDef($tCategory, [
            BDb::COLUMNS => [
                'is_enabled' => 'TINYINT(1) UNSIGNED DEFAULT 1 AFTER num_products',
            ],
            //TODO: figure out which keys are needed
        ]);
        $this->FCom_Catalog_Model_Category->update_many(['show_products' => 1, 'show_sidebar' => 1, 'is_enabled' => 1]);
    }

    public function upgrade__0_2_14__0_2_15()
    {
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $this->BDb->ddlTableDef($tProduct, [
            BDb::COLUMNS => [
                'position' => 'SMALLINT(6) UNSIGNED DEFAULT NULL'
            ]
        ]);
    }

    public function upgrade__0_2_15__0_2_16()
    {
        $tCategory = $this->FCom_Catalog_Model_Category->table();
        $this->BDb->ddlTableDef($tCategory, [
            BDb::COLUMNS => [
                'show_view' => 'tinyint(1) unsigned default 0',
                'view_name' => 'varchar(255)',
                'page_parts' => 'varchar(50)',
            ],
        ]);
    }

    public function upgrade__0_2_16__0_2_17()
    {
        $tMedia = $this->FCom_Catalog_Model_ProductMedia->table();
        $this->BDb->ddlTableDef($tMedia, [
            BDb::COLUMNS => [
                'main_thumb' => 'tinyint(1) unsigned default 0',
            ],
        ]);
    }

    public function upgrade__0_2_17__0_2_18()
    {
        /*
        $tCategory = $this->FCom_Catalog_Model_Category->table();
        $this->BDb->ddlTableDef($tCategory, array(
            BDb::COLUMNS => array(
                'show_cms_page' => 'tinyint(1) unsigned default null',
                'cms_page' => 'text default null',
            ),
        ));
        */
    }

    public function upgrade__0_2_18__0_2_19()
    {
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $this->BDb->ddlTableDef($tProduct, [
            BDb::KEYS => [
                'UNQ_product_name' => BDb::DROP,
            ],
        ]);
    }

    public function upgrade__0_2_19__0_2_20()
    {
        $tProductLink = $this->FCom_Catalog_Model_ProductLink->table();
        $this->BDb->ddlTableDef($tProductLink, [
            BDb::COLUMNS => [
                'link_type'     => "enum('related','similar', 'cross-sell') NOT NULL",
            ],
        ]);
    }

    public function upgrade__0_2_20__0_2_21()
    {
        /*
        $tProductLink = $this->FCom_Catalog_Model_ProductLink->table();
        $this->BDb->ddlTableDef($tProductLink, array(
            BDb::COLUMNS => array(
                'link_type'     => "enum('related','similar', 'cross_sell') NOT NULL",
            ),
        ));
        */
    }

    public function upgrade__0_2_21__0_2_22()
    {
        $tCategory = $this->FCom_Catalog_Model_Category->table();
        $this->BDb->ddlTableDef($tCategory, [
            BDb::COLUMNS => [
                'id_path' => 'varchar(50) null',
            ],
        ]);
    }

    public function upgrade__0_2_22__0_2_23()
    {
        $tCategory = $this->FCom_Catalog_Model_Category->table();
        $this->BDb->ddlTableDef($tCategory, [
            BDb::COLUMNS => [
                'image_url' => 'TEXT null',
            ],
        ]);
    }

    public function upgrade__0_2_23__0_2_24()
    {
        $tProductLink = $this->FCom_Catalog_Model_ProductLink->table();
        $this->BDb->ddlTableDef($tProductLink, [
            BDb::COLUMNS => [
                'position' => 'smallint(6) null',
            ],
        ]);
    }

    public function upgrade__0_2_24__0_2_25()
    {
        $tProductLink = $this->FCom_Catalog_Model_ProductLink->table();
        $this->BDb->ddlTableDef($tProductLink, [
            BDb::COLUMNS => [
                'link_type' => "varchar(20) NOT NULL",
            ],
        ]);
    }

    public function upgrade__0_2_25__0_2_26()
    {
        $tCategory = $this->FCom_Catalog_Model_Category->table();
        $this->BDb->ddlTableDef($tCategory, [
            BDb::COLUMNS => [
                'is_featured'   => 'TINYINT(3) UNSIGNED DEFAULT NULL',
            ],
            BDb::KEYS => [
                'IDX_featured'  => '(is_featured)',
            ],
        ]);
    }

    public function upgrade__0_2_26__0_2_27()
    {
        $tCategory = $this->FCom_Catalog_Model_Category->table();
        $this->BDb->ddlTableDef($tCategory, [
            BDb::COLUMNS => [
                'featured_image_url'   => 'TEXT NULL',
                'nav_callout_image_url'   => 'TEXT NULL',
            ],
        ]);
    }

    public function upgrade__0_2_27__0_3_0()
    {
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $tBin = $this->FCom_Catalog_Model_InventoryBin->table();
        $tSku = $this->FCom_Catalog_Model_InventorySku->table();
        $tSkuHistory = $this->FCom_Catalog_Model_InventorySkuHistory->table();
        $tProdHistory = $this->FCom_Catalog_Model_ProductHistory->table();

        $this->BDb->ddlTableDef($tProduct, [
            BDb::COLUMNS => [
                'local_sku' => 'RENAME product_sku varchar(100) not null',
                'inventory_sku' => 'varchar(100) default null',
            ],
            BDb::KEYS => [
                'IDX_inventory_sku' => '(inventory_sku)',
            ],
        ]);

        $this->BDb->ddlTableDef($tBin, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'title' => 'varchar(50)',
                'description' => 'text',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
        ]);

        $this->BDb->ddlTableDef($tSku, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'inventory_sku' => 'varchar(50) not null',
                'title' => 'varchar(255) not null',
                'description' => 'text',
                'is_salable' => 'tinyint',
                'bin_id' => 'int unsigned null',
                'unit_cost' => 'decimal(12,2)',
                'net_weight'  => 'decimal(12,2) null default null',
                'shipping_weight' => 'decimal(12,2) null default null',
                'shipping_size' => 'varchar(30)',
                'pack_separate' => 'tinyint not null default 0',
                'qty_in_stock' => 'int not null',
                'qty_warn_customer' => 'int unsigned null',
                'qty_notify_admin' => 'int unsigned null',
                'qty_cart_min' => 'int unsigned null',
                'qty_cart_inc' => 'int unsigned not null default 1',
                'qty_buffer' => 'int unsigned not null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_inventory_sku' => 'UNIQUE (inventory_sku)',
            ],
            BDb::CONSTRAINTS => [
                'bin' => ['bin_id', $tBin],
            ],
        ]);

        $this->BDb->ddlTableDef($tSkuHistory, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'sku_id' => 'int unsigned not null',
                'unit_cost' => 'decimal(12,2)',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_create' => '(create_at)',
                'IDX_sku_create' => '(sku_id, create_at)',
            ],
            BDb::CONSTRAINTS => [
                'sku' => ['sku_id', $tSku],
            ],
        ]);

        $this->BDb->ddlTableDef($tProdHistory, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'version_code' => 'varchar(50)',
                'version_notes' => 'text',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_create' => '(create_at)',
                'IDX_product_create' => '(product_id, create_at)',
            ],
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct],
            ],
        ]);

    }

    public function upgrade__0_3_0__0_3_1()
    {
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $tSku = $this->FCom_Catalog_Model_InventorySku->table();

        $this->BDb->ddlTableDef($tProduct, [
            BDb::COLUMNS => [
                'manage_inventory' => 'tinyint not null default 0',
            ],
            BDb::KEYS => [
                'IDX_manage_inventory' => '(manage_inventory)',
            ],
        ]);

        $this->BDb->ddlTableDef($tSku, [
            BDb::COLUMNS => [
                'allow_backorder' => 'tinyint not null default 0',
            ],
        ]);
    }

    public function upgrade__0_3_1__0_3_2()
    {
        $tSku = $this->FCom_Catalog_Model_InventorySku->table();
        $this->BDb->ddlTableDef($tSku, [
            BDb::COLUMNS => [
                'qty_reserved' => 'int unsigned not null default 0 after qty_buffer',
                'manage_inventory' => 'tinyint not null default 0',
            ],
            BDb::KEYS => [
                'IDX_manage_inventory' => '(manage_inventory)',
            ],
        ]);
    }
}
