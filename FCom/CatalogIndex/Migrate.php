<?php
class FCom_CatalogIndex_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
    }

    public function install()
    {
        $tIdxTerm = FCom_CatalogIndex_Model_Term::table();
        $tIdxField = FCom_CatalogIndex_Model_Field::table();
        $tField = FCom_CustomField_Model_Field::table();
        $tIdxDoc = FCom_CatalogIndex_Model_Doc::table();
        $tProduct = FCom_Catalog_Model_Product::table();
        $tIdxDocTerm = FCom_CatalogIndex_Model_DocTerm::table();

        BDb::ddlTableDef($tIdxTerm, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'term' => 'varchar(50) not null',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_term' => 'UNIQUE (term)',
            ),
        ));
        BDb::ddlTableDef($tIdxField, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'field_name' => 'varchar(50) not null',
                'field_type' => "enum('int','decimal','varchar','text','category') not null",
                'weight' => 'int not null',
                'fcom_field_id' => 'int unsigned default null',
            ),
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => array(
                "FK_{$tIdxField}_field" => "FOREIGN KEY (`fcom_field_id`) REFERENCES {$tField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ),
        ));
        BDb::ddlTableDef($tIdxDoc, array(
            'COLUMNS' => array(
                'id' => 'int(10) unsigned not null auto_increment',
                'last_indexed' => 'datetime not null',
                'sort_name' => 'varchar(50)',
                'sort_price' => 'decimal(12,2)',
                'sort_rating' => 'tinyint',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_last_indexed' => '(last_indexed)',
                'IDX_sort_name' => '(sort_name)',
                'IDX_sort_price' => '(sort_price)',
                'IDX_sort_rating' => '(sort_rating)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tIdxDoc}_product" => "FOREIGN KEY (`id`) REFERENCES {$tProduct} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ),
        ));
        BDb::ddlTableDef($tIdxDocTerm, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'doc_id' => 'int(10) unsigned NOT NULL',
                'field_id' => 'int(10) unsigned NOT NULL',
                'term_id' => 'int(10) unsigned NOT NULL',
                'position' => 'int(11) DEFAULT NULL',
            ),
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => array(
                "FK_{$tIdxDocTerm}_doc" => "FOREIGN KEY (`doc_id`) REFERENCES {$tIdxDoc} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tIdxDocTerm}_field" => "FOREIGN KEY (`field_id`) REFERENCES {$tIdxField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tIdxDocTerm}_term" => "FOREIGN KEY (`term_id`) REFERENCES {$tIdxTerm} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ),
        ));
    }

    public function upgrade_0_1_1()
    {
        //TODO: rename `doc` fields: sort_name -> sort_product_name (update key)
        //TODO: add `field` columns: filter_multivalue tinyint, filter_show_empty tinyint
    }

}
