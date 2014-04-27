<?php
class FCom_CatalogIndex_Migrate extends BClass
{
    public function install__0_1_9()
    {
        $tCustField = FCom_CustomField_Model_Field::table();
        $tProduct = FCom_Catalog_Model_Product::table();

        $tTerm = FCom_CatalogIndex_Model_Term::table();
        $tField = FCom_CatalogIndex_Model_Field::table();
        $tFieldValue = FCom_CatalogIndex_Model_FieldValue::table();
        $tDoc = FCom_CatalogIndex_Model_Doc::table();
        $tDocValue = FCom_CatalogIndex_Model_DocValue::table();
        $tDocTerm = FCom_CatalogIndex_Model_DocTerm::table();
        $tDocSort = FCom_CatalogIndex_Model_DocSort::table();
        BDb::ddlTableDef( $tTerm, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'term' => 'varchar(50) not null',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_term' => 'UNIQUE (term)',
            ],
        ] );
        BDb::ddlTableDef( $tField, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'field_name' => 'varchar(50) not null',
                'field_label' => 'varchar(50) not null',
                'field_type' => "enum('int','decimal','varchar','text','category') not null",
                'weight' => 'int unsigned not null',
                'fcom_field_id' => 'int(10) unsigned default null',
                'source_type' => "enum('field','method','callback') not null default 'field'",
                'source_callback' => 'varchar(255) null',
                'filter_type' => "ENUM('none','exclusive','inclusive','range') NOT NULL DEFAULT 'none'",
                //'filter_multiselect' => 'tinyint not null default 0',
                'filter_multivalue' => 'tinyint not null default 0',
                'filter_counts' => 'tinyint unsigned NOT NULL DEFAULT 0',
                'filter_show_empty' => 'tinyint not null default 0',
                'filter_order' => 'smallint unsigned',
                'filter_custom_view' => 'varchar(255)',
                'search_type' => "enum('none','terms') not null",
                'sort_type' => "enum('none','asc','desc','both') not null",
                'sort_label' => 'varchar(255)',
                'sort_order' => 'tinyint unsigned',
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tField}_field" => "FOREIGN KEY (`fcom_field_id`) REFERENCES {$tCustField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ] );
        BDb::ddlTableDef( $tFieldValue, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'field_id' => 'int unsigned not null',
                'val' => 'varchar(100) not null',
                'display' => 'varchar(100) default null',
                'sort_order' => 'smallint unsigned default null',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'field_id' => 'UNIQUE (field_id,val)',
                'IDX_sort_order' => '(field_id, sort_order)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tFieldValue}_field" => "FOREIGN KEY (`field_id`) REFERENCES {$tField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ] );
        BDb::ddlTableDef( $tDoc, [
            'COLUMNS' => [
                'id' => 'int(10) unsigned not null auto_increment',
                'last_indexed' => 'datetime not null',
                'flag_reindex' => 'tinyint not null default 0',
                'sort_product_name' => 'varchar(50)',
                'sort_price' => 'decimal(12,2)',
                'sort_rating' => 'tinyint',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_last_indexed' => '(last_indexed)',
                'IDX_flag_reindex' => '(flag_reindex)',
                'IDX_sort_product_name' => '(sort_product_name)',
                'IDX_sort_price' => '(sort_price)',
                'IDX_sort_rating' => '(sort_rating)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tDoc}_product" => "FOREIGN KEY (`id`) REFERENCES {$tProduct} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ] );
        BDb::ddlTableDef( $tDocTerm, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'doc_id' => 'int(10) unsigned NOT NULL',
                'field_id' => 'int(10) unsigned NOT NULL',
                'term_id' => 'int(10) unsigned NOT NULL',
                'position' => 'int(11) DEFAULT NULL',
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tDocTerm}_doc" => "FOREIGN KEY (`doc_id`) REFERENCES {$tDoc} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tDocTerm}_field" => "FOREIGN KEY (`field_id`) REFERENCES {$tField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tDocTerm}_term" => "FOREIGN KEY (`term_id`) REFERENCES {$tTerm} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ] );
        BDb::ddlTableDef( $tDocValue, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'doc_id' => 'int(10) unsigned NOT NULL',
                'field_id' => 'int(10) unsigned NOT NULL',
                'value_id' => 'int(10) unsigned NOT NULL',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_doc_field_value' => 'UNIQUE (`doc_id`,`field_id`,`value_id`)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tDocValue}_doc" => "FOREIGN KEY (`doc_id`) REFERENCES {$tDoc} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tDocValue}_field" => "FOREIGN KEY (`field_id`) REFERENCES {$tField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tDocValue}_value" => "FOREIGN KEY (`value_id`) REFERENCES {$tFieldValue} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ] );

        $this->upgrade__0_1_3__0_1_4();
        FCom_CatalogIndex_Model_Field::i()->update_many( [
            'source_type' => 'callback',
            'source_callback' => 'FCom_CatalogIndex_Model_Field::indexPrice',
        ], [ 'field_name' => 'price' ] );

        BDb::ddlTableDef( $tDocSort, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'doc_id' => 'int unsigned not null',
                'field_id' => 'int unsigned not null',
                'value' => 'varchar(255) not null',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_field_value' => '(field_id, value)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tDocSort}_doc" => "FOREIGN KEY (doc_id) REFERENCES {$tDoc} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tDocSort}_field" => "FOREIGN KEY (field_id) REFERENCES {$tField} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ],
        ] );
    }

    public function upgrade__0_1_3__0_1_4()
    {
        //$this->install();
        BDb::run( "
REPLACE  INTO `fcom_index_field`
(`id`,`field_name`,`field_label`,`field_type`,`weight`,`fcom_field_id`,`source_type`,`source_callback`,`filter_type`,`filter_multivalue`,`filter_counts`,`filter_show_empty`,`filter_order`,`filter_custom_view`,`search_type`,`sort_type`,`sort_label`,`sort_order`)
VALUES
(1,'product_name','Product Name','text',0,NULL,'field',NULL,'none',0,0,0,NULL,NULL,'terms','both','Product Name (A-Z) || Product Name (Z-A)',NULL),
(2,'short_description','Short Description','text',0,NULL,'field',NULL,'none',0,0,0,NULL,NULL,'terms','none',NULL,NULL),
(3,'description','Description','text',0,NULL,'field',NULL,'none',0,0,0,NULL,NULL,'terms','none',NULL,NULL),
(4,'category','Category','category',0,NULL,'callback','FCom_CatalogIndex_Model_Field::indexCategory','exclusive',1,1,0,1,'catalog/category/_filter_categories','none','none',NULL,NULL),
(6,'color','Color','varchar',0,NULL,'field',NULL,'inclusive',0,1,0,2,NULL,'none','none',NULL,NULL),
(7,'size','Size','varchar',0,NULL,'field',NULL,'inclusive',0,1,0,3,NULL,'none','none',NULL,NULL),
(8,'price_range','Price Range','varchar',0,NULL,'callback','FCom_CatalogIndex_Model_Field::indexPriceRange','inclusive',0,1,0,4,NULL,'none','none',NULL,NULL),
(9,'price','Price','decimal',0,NULL,'field',NULL,'none',0,0,0,4,NULL,'none','both','Price (Min-Max) || Price (Max-Min)',NULL)
        " );
    }

    public function upgrade__0_1_4__0_1_5()
    {
        BDb::ddlTableDef( FCom_CatalogIndex_Model_Field::table(), [
            'COLUMNS' => [
                'filter_counts' => 'tinyint unsigned NOT NULL DEFAULT 0 AFTER filter_multivalue',
            ],
        ] );

        FCom_CatalogIndex_Model_Field::i()->update_many(
            [ 'filter_custom_view' => 'catalogindex/product/_filter_categories' ],
            [ 'field_name' => 'category' ]
        );
    }

    public function upgrade__0_1_5__0_1_6()
    {
        BDb::ddlTableDef( FCom_CatalogIndex_Model_Field::table(), [
            'COLUMNS' => [
                'filter_multiselect' => 'DROP',
            ],
        ] );
    }

    public function upgrade__0_1_6__0_1_7()
    {
        BDb::ddlTableDef( FCom_CatalogIndex_Model_Doc::table(), [
            'COLUMNS' => [
                'flag_reindex' => 'tinyint not null default 0 after last_indexed',
            ],
            'KEYS' => [
                'IDX_flag_reindex' => '(flag_reindex)',
            ],
        ] );
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $tDoc = FCom_CatalogIndex_Model_Doc::table();
        $tField = FCom_CatalogIndex_Model_Field::table();
        $tDocSort = FCom_CatalogIndex_Model_DocSort::table();

        BDb::ddlTableDef( $tDocSort, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'doc_id' => 'int unsigned not null',
                'field_id' => 'int unsigned not null',
                'value' => 'varchar(255) not null',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_field_value' => '(field_id, value)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tDocSort}_doc" => "FOREIGN KEY (doc_id) REFERENCES {$tDoc} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tDocSort}_field" => "FOREIGN KEY (field_id) REFERENCES {$tField} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ],
        ] );
        //TODO: delete doc.sort_product_name
    }

    public function upgrade__0_1_8__0_1_9()
    {
        FCom_CatalogIndex_Model_Field::i()->update_many( [
            'source_type' => 'callback',
            'source_callback' => 'FCom_CatalogIndex_Model_Field::indexPrice',
        ], [ 'field_name' => 'price' ] );
    }
}
