<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogIndex_Migrate
 *
 * @property Sellvana_CatalogIndex_Model_Doc $Sellvana_CatalogIndex_Model_Doc
 * @property Sellvana_CatalogIndex_Model_DocSort $Sellvana_CatalogIndex_Model_DocSort
 * @property Sellvana_CatalogIndex_Model_DocTerm $Sellvana_CatalogIndex_Model_DocTerm
 * @property Sellvana_CatalogIndex_Model_DocValue $Sellvana_CatalogIndex_Model_DocValue
 * @property Sellvana_CatalogIndex_Model_Field $Sellvana_CatalogIndex_Model_Field
 * @property Sellvana_CatalogIndex_Model_FieldValue $Sellvana_CatalogIndex_Model_FieldValue
 * @property Sellvana_CatalogIndex_Model_Term $Sellvana_CatalogIndex_Model_Term
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_CustomField_Model_Field $Sellvana_CustomField_Model_Field
 */

class Sellvana_CatalogIndex_Migrate extends BClass
{
    public function install__0_2_0()
    {
        $tCustField = $this->Sellvana_CustomField_Model_Field->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();

        $tTerm = $this->Sellvana_CatalogIndex_Model_Term->table();
        $tField = $this->Sellvana_CatalogIndex_Model_Field->table();
        $tFieldValue = $this->Sellvana_CatalogIndex_Model_FieldValue->table();
        $tDoc = $this->Sellvana_CatalogIndex_Model_Doc->table();
        $tDocValue = $this->Sellvana_CatalogIndex_Model_DocValue->table();
        $tDocTerm = $this->Sellvana_CatalogIndex_Model_DocTerm->table();
        $tDocSort = $this->Sellvana_CatalogIndex_Model_DocSort->table();
        $this->BDb->ddlTableDef($tTerm, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'term' => 'varchar(50) not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_term' => 'UNIQUE (term)',
            ],
        ]);
        $this->BDb->ddlTableDef($tField, [
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'field' => ['fcom_field_id', $tCustField],
            ],
        ]);
        $this->BDb->ddlTableDef($tFieldValue, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'field_id' => 'int unsigned not null',
                'val' => 'varchar(100) not null',
                'display' => 'varchar(100) default null',
                'sort_order' => 'smallint unsigned default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'field_id' => 'UNIQUE (field_id,val)',
                'IDX_sort_order' => '(field_id, sort_order)',
            ],
            BDb::CONSTRAINTS => [
                'field' => ['field_id', $tField],
            ],
        ]);
        $this->BDb->ddlTableDef($tDoc, [
            BDb::COLUMNS => [
                'id' => 'int(10) unsigned not null auto_increment',
                'last_indexed' => 'datetime not null',
                'flag_reindex' => 'tinyint not null default 0',
                'sort_product_name' => 'varchar(50)',
                'sort_price' => 'decimal(12,2)',
                'sort_rating' => 'tinyint',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_last_indexed' => '(last_indexed)',
                'IDX_flag_reindex' => '(flag_reindex)',
                'IDX_sort_product_name' => '(sort_product_name)',
                'IDX_sort_price' => '(sort_price)',
                'IDX_sort_rating' => '(sort_rating)',
            ],
            BDb::CONSTRAINTS => [
                'product' => ['id', $tProduct],
            ],
        ]);
        $this->BDb->ddlTableDef($tDocTerm, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'doc_id' => 'int(10) unsigned NOT NULL',
                'field_id' => 'int(10) unsigned NOT NULL',
                'term_id' => 'int(10) unsigned NOT NULL',
                'position' => 'int(11) DEFAULT NULL',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'doc' => ['doc_id', $tDoc],
                'field' => ['field_id', $tField],
                'term' => ['term_id', $tTerm],
            ],
        ]);
        $this->BDb->ddlTableDef($tDocValue, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'doc_id' => 'int(10) unsigned NOT NULL',
                'field_id' => 'int(10) unsigned NOT NULL',
                'value_id' => 'int(10) unsigned NOT NULL',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_doc_field_value' => 'UNIQUE (`doc_id`,`field_id`,`value_id`)',
            ],
            BDb::CONSTRAINTS => [
                'doc' => ['doc_id', $tDoc],
                'field' => ['field_id', $tField],
                'value' => ['value_id', $tFieldValue],
            ],
        ]);

        $this->upgrade__0_1_3__0_1_4();
        $this->Sellvana_CatalogIndex_Model_Field->update_many([
            'source_type' => 'callback',
            'source_callback' => 'Sellvana_CatalogIndex_Model_Field::indexPrice',
        ], ['field_name' => 'price']);

        $this->BDb->ddlTableDef($tDocSort, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'doc_id' => 'int unsigned not null',
                'field_id' => 'int unsigned not null',
                'value' => 'varchar(255) not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_field_value' => '(field_id, value)',
            ],
            BDb::CONSTRAINTS => [
                'doc' => ['doc_id', $tDoc],
                'field' => ['field_id', $tField],
            ],
        ]);
    }

    public function upgrade__0_1_3__0_1_4()
    {
        /*
        $fields = [
            [
                'field_name' => ,
                'field_label' => ,
                'field_type' => ,
                'weight' => ,
                'fcom_field_id' => ,
                'source_type' => ,
                'source_callback' => ,
                'filter_type' => ,
                'filter_multivalue' => ,
                'filter_counts' => ,
                'filter_show_empty' => ,
                'filter_order' => ,
                'filter_custom_view' => ,
                'search_type' => ,
                'sort_type' => ,
                'sort_label' => ,
                'sort_order' => ,
            ],
        ];
(1,'product_name','Product Name','text',0,NULL,'field',NULL,'none',0,0,0,NULL,NULL,'terms','both','Product Name (A-Z) || Product Name (Z-A)',NULL),
(2,'short_description','Short Description','text',0,NULL,'field',NULL,'none',0,0,0,NULL,NULL,'terms','none',NULL,NULL),
(3,'description','Description','text',0,NULL,'field',NULL,'none',0,0,0,NULL,NULL,'terms','none',NULL,NULL),
(4,'category','Category','category',0,NULL,'callback','Sellvana_CatalogIndex_Model_Field::indexCategory','exclusive',1,1,0,1,'catalog/category/_filter_categories','none','none',NULL,NULL),
(6,'color','Color','varchar',0,NULL,'field',NULL,'inclusive',0,1,0,2,NULL,'none','none',NULL,NULL),
(7,'size','Size','varchar',0,NULL,'field',NULL,'inclusive',0,1,0,3,NULL,'none','none',NULL,NULL),
(8,'price_range','Price Range','varchar',0,NULL,'callback','Sellvana_CatalogIndex_Model_Field::indexPriceRange','inclusive',0,1,0,4,NULL,'none','none',NULL,NULL),
(9,'price','Price','decimal',0,NULL,'field',NULL,'none',0,0,0,4,NULL,'none','both','Price (Min-Max) || Price (Max-Min)',NULL)
        */
        
        $tField = $this->Sellvana_CatalogIndex_Model_Field->table();
        //$this->install();
        $this->BDb->run("
REPLACE  INTO `{$tField}`
(`id`,`field_name`,`field_label`,`field_type`,`weight`,`fcom_field_id`,`source_type`,`source_callback`,`filter_type`,`filter_multivalue`,`filter_counts`,`filter_show_empty`,`filter_order`,`filter_custom_view`,`search_type`,`sort_type`,`sort_label`,`sort_order`)
VALUES
(1,'product_name','Product Name','text',0,NULL,'field',NULL,'none',0,0,0,NULL,NULL,'terms','both','Product Name (A-Z) || Product Name (Z-A)',NULL),
(2,'short_description','Short Description','text',0,NULL,'field',NULL,'none',0,0,0,NULL,NULL,'terms','none',NULL,NULL),
(3,'description','Description','text',0,NULL,'field',NULL,'none',0,0,0,NULL,NULL,'terms','none',NULL,NULL),
(4,'category','Category','category',0,NULL,'callback','Sellvana_CatalogIndex_Model_Field::indexCategory','exclusive',1,1,0,1,'catalog/category/_filter_categories','none','none',NULL,NULL),
(6,'color','Color','varchar',0,NULL,'field',NULL,'inclusive',0,1,0,2,NULL,'none','none',NULL,NULL),
(7,'size','Size','varchar',0,NULL,'field',NULL,'inclusive',0,1,0,3,NULL,'none','none',NULL,NULL),
(8,'price_range','Price Range','varchar',0,NULL,'callback','Sellvana_CatalogIndex_Model_Field::indexPriceRange','inclusive',0,1,0,4,NULL,'none','none',NULL,NULL),
(9,'price','Price','decimal',0,NULL,'field',NULL,'none',0,0,0,4,NULL,'none','both','Price (Min-Max) || Price (Max-Min)',NULL)
        ");
    }

    public function upgrade__0_1_4__0_1_5()
    {
        $this->BDb->ddlTableDef($this->Sellvana_CatalogIndex_Model_Field->table(), [
            BDb::COLUMNS => [
                'filter_counts' => 'tinyint unsigned NOT NULL DEFAULT 0 AFTER filter_multivalue',
            ],
        ]);

        $this->Sellvana_CatalogIndex_Model_Field->update_many(
            ['filter_custom_view' => 'catalogindex/product/_filter_categories'],
            ['field_name' => 'category']
        );
    }

    public function upgrade__0_1_5__0_1_6()
    {
        $this->BDb->ddlTableDef($this->Sellvana_CatalogIndex_Model_Field->table(), [
            BDb::COLUMNS => [
                'filter_multiselect' => BDb::DROP,
            ],
        ]);
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $this->BDb->ddlTableDef($this->Sellvana_CatalogIndex_Model_Doc->table(), [
            BDb::COLUMNS => [
                'flag_reindex' => 'tinyint not null default 0 after last_indexed',
            ],
            BDb::KEYS => [
                'IDX_flag_reindex' => '(flag_reindex)',
            ],
        ]);
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $tDoc = $this->Sellvana_CatalogIndex_Model_Doc->table();
        $tField = $this->Sellvana_CatalogIndex_Model_Field->table();
        $tDocSort = $this->Sellvana_CatalogIndex_Model_DocSort->table();

        $this->BDb->ddlTableDef($tDocSort, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'doc_id' => 'int unsigned not null',
                'field_id' => 'int unsigned not null',
                'value' => 'varchar(255) not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_field_value' => '(field_id, value)',
            ],
            BDb::CONSTRAINTS => [
                'doc' => ['doc_id', $tDoc],
                'field' => ['field_id', $tField],
            ],
        ]);
        //TODO: delete doc.sort_product_name
    }

    public function upgrade__0_1_8__0_1_9()
    {
        $this->Sellvana_CatalogIndex_Model_Field->update_many([
            'source_type' => 'callback',
            'source_callback' => 'Sellvana_CatalogIndex_Model_Field::indexPrice',
        ], ['field_name' => 'price']);
    }

    public function upgrade__0_1_9__0_2_0()
    {
        $origRegex = 'FCom_(CatalogIndex|ProductReviews)';
        $tField = $this->Sellvana_CatalogIndex_Model_Field->table();
        $this->BDb->run("UPDATE {$tField} SET source_callback=replace(source_callback, 'FCom_', 'Sellvana_') WHERE source_callback REGEXP '{$origRegex}'");
    }

    public function upgrade__0_2_0__0_2_1()
    {
        $tDocValue = $this->Sellvana_CatalogIndex_Model_DocValue->table();
        $this->BDb->ddlTableDef($tDocValue, [
            BDb::COLUMNS => [
                'value_id' => 'int unsigned default null',
                'value_decimal' => 'decimal(12,2) default null',
            ],
            BDb::KEYS => [
                'IDX_value_decimal' => '(value_decimal)',
            ],
        ]);
    }

    public function upgrade__0_2_1__0_2_2()
    {
        $priceField = $this->Sellvana_CatalogIndex_Model_Field->load('price', 'field_name');
        if ($priceField) {
            $priceField->set(['filter_type' => 'range', 'filter_custom_view' => 'catalog/category/_filter_price'])->save();
        }
    }
}
