<?php

class FCom_IndexTank_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
        BMigrate::upgrade('0.1.1', '0.1.2', array($this, 'upgrade_0_1_2'));
        BMigrate::upgrade('0.1.2', '0.1.3', array($this, 'upgrade_0_1_3'));
        BMigrate::upgrade('0.1.3', '0.1.4', array($this, 'upgrade_0_1_4'));
        BMigrate::upgrade('0.1.4', '0.1.5', array($this, 'upgrade_0_1_5'));
        BMigrate::upgrade('0.1.5', '0.1.6', array($this, 'upgrade_0_1_6'));
        BMigrate::upgrade('0.1.6', '0.1.7', array($this, 'upgrade_0_1_7'));
        BMigrate::upgrade('0.1.7', '0.1.8', array($this, 'upgrade_0_1_8'));
        BMigrate::upgrade('0.1.8', '0.1.9', array($this, 'upgrade_0_1_9'));
    }

    public function uninstall()
    {
        $productsTable = FCom_Catalog_Model_Product::table();
        BDb::run( " ALTER TABLE {$productsTable} DROP indextank_indexed,
        DROP indextank_indexed_at; ");

        $pIndexHelperTable = FCom_IndexTank_Model_IndexHelper::table();
        BDb::run( " DROP TABLE {$pIndexHelperTable}; ");

        $pFieldsTable = FCom_IndexTank_Model_ProductField::table();
        BDb::run( " DROP TABLE {$pFieldsTable}; ");

        $pFunctionsTable = FCom_IndexTank_Model_ProductFunction::table();
        BDb::run( " DROP TABLE {$pFunctionsTable}; ");

    }


    public function upgrade_0_1_1()
    {
        $productsTable = FCom_Catalog_Model_Product::table();
        BDb::run( " ALTER TABLE {$productsTable} ADD indextank_indexed tinyint(1) not null default 0,
        ADD indextank_indexed_at datetime not null; ");


        $pIndexingStatusTable = FCom_IndexTank_Model_IndexingStatus::table();
        BDb::run( "
            CREATE TABLE IF NOT EXISTS {$pIndexingStatusTable} (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `task` VARCHAR( 255 ) NOT NULL ,
            `info` VARCHAR( 255 ) NOT NULL ,
            `updated_at` datetime
            ) ENGINE = InnoDB;
         ");

    }

    public function upgrade_0_1_2()
    {
        $pFunctionsTable = FCom_IndexTank_Model_ProductFunction::table();
        BDb::run( " ALTER TABLE {$pFunctionsTable} MODIFY `number` int(11) NOT NULL DEFAULT '-1'");
    }

    public function upgrade_0_1_3()
    {
        $productsTable = FCom_Catalog_Model_Product::table();
        BDb::run( " ALTER TABLE {$productsTable} ADD INDEX (indextank_indexed); ");
    }

    public function upgrade_0_1_4()
    {
        $pIndexingStatusTable = FCom_IndexTank_Model_IndexingStatus::table();
        BDb::run( " ALTER TABLE {$pIndexingStatusTable}
        ADD `status` enum('start','stop','pause') NOT NULL,
        ADD `percent` BIGINT( 11 ) NOT NULL ,
        ADD `indexed` BIGINT( 11 ) NOT NULL ; ");
    }

    public function upgrade_0_1_5()
    {
        $pIndexingStatusTable = FCom_IndexTank_Model_IndexingStatus::table();
        BDb::run( " ALTER TABLE {$pIndexingStatusTable}
        MODIFY `status` ENUM( 'start', 'pause' ) NOT NULL DEFAULT 'start'; ");
    }

    public function upgrade_0_1_6()
    {
        $pIndexingStatusTable = FCom_IndexTank_Model_IndexingStatus::table();
        BDb::run( " ALTER TABLE {$pIndexingStatusTable} ADD `to_index` BIGINT( 11 ) NOT NULL ;");
    }

    public function upgrade_0_1_7()
    {
        $pIndexingStatusTable = FCom_IndexTank_Model_IndexingStatus::table();
        BDb::run( " ALTER TABLE {$pIndexingStatusTable} ADD `index_size` BIGINT( 11 ) NOT NULL ;");
    }

    public function upgrade_0_1_8()
    {
        $pPFTable = FCom_IndexTank_Model_ProductFunction::table();
        BDb::run( " ALTER TABLE {$pPFTable} ADD `label` varchar(100) NOT NULL ;");
    }

    public function upgrade_0_1_9()
    {
        $pPFTable = FCom_IndexTank_Model_ProductFunction::table();
        $sql = "
        update {$pPFTable} set label = 'Newest first' where name='age';
        update {$pPFTable} set label = 'Relevance' where name='relevance';
        update {$pPFTable} set label = 'Price (Lower first)' where name='base_price_asc';
        update {$pPFTable} set label = 'Price (Higher first)' where name='base_price_desc';
        update {$pPFTable} set label = 'Product name (A-Z)' where name='product_name_asc';
        update {$pPFTable} set label = 'Product name (Z-A)' where name='product_name_desc';
        update {$pPFTable} set label = 'Manuf SKU (A-Z)' where name='manuf_sku_asc';
        update {$pPFTable} set label = 'Manuf SKU (Z-A)' where name='manuf_sku_desc';
        ";
        BDb::run( $sql );
    }


    public function install()
    {
        $pIndexHelperTable = FCom_IndexTank_Model_IndexHelper::table();
        BDb::run( "
            CREATE TABLE IF NOT EXISTS {$pIndexHelperTable} (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `index` VARCHAR( 255 ) NOT NULL ,
            `checkpoint` TIMESTAMP
            ) ENGINE = InnoDB;
         ");
        BDb::i()->ddlClearCache();
        BDb::run("insert into {$pIndexHelperTable} (`index`, checkpoint) values('products', null)");

        //create table
        $pFieldsTable = FCom_IndexTank_Model_ProductField::table();
        BDb::run( "
            CREATE TABLE IF NOT EXISTS {$pFieldsTable} (
            `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
            `field_name` varchar(1024) NOT NULL DEFAULT '',
            `field_nice_name` varchar(1024) NOT NULL DEFAULT '',
            `field_type`    varchar(255) not null default '',
            `search` tinyint(1) not null default 0,
            `facets` tinyint(1) not null default 0,
            `scoring` tinyint(1) not null default 0,
            `var_number` tinyint(3) not null default -1,
            `priority` int(11) unsigned NOT NULL DEFAULT '1',
            `filter` enum('','inclusive','exclusive') NOT NULL DEFAULT '',
            `source_type` varchar(255) NOT NULL,
            `source_value` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
            )ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        BDb::i()->ddlClearCache();

        //add initial data
        $this->installProductSchema();

        //create table
        $pFunctionsTable = FCom_IndexTank_Model_ProductFunction::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$pFunctionsTable} (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `name` VARCHAR( 1024 ) NOT NULL,
            `number` INT( 11 ) UNSIGNED NOT NULL ,
            `definition` VARCHAR( 1024 ) NOT NULL
            ) ENGINE = InnoDB;
            ");
        BDb::i()->ddlClearCache();

        //predefined functions
        $functions  =  array (
                'age'                   => array('number' => 0, 'definition' => '-age'         ),
                'relevance'             => array('number' => 1, 'definition' => 'relevance'    ),
                'base_price_asc'        => array('number' => 2, 'definition' => '-d[0]'  ),
                'base_price_desc'       => array('number' => 3, 'definition' => 'd[0]'   ),
                'product_name_asc'        => array('number' => 4, 'definition' => '-d[1]'  ),
                'product_name_desc'       => array('number' => 5, 'definition' => 'd[1]'   ),
                'manuf_sku_asc'        => array('number' => 6, 'definition' => '-d[2]'  ),
                'manuf_sku_desc'       => array('number' => 7, 'definition' => 'd[2]'   ),
        );
        $functionsList = FCom_IndexTank_Model_ProductFunction::i()->getList();
        //add initial functions
        foreach ($functions as $func_name => $func) {
            //add new function only if function not exists yet
            if (!empty($functionsList[$func['number']])) {
                continue;
            }
            BDb::run("insert into {$pFunctionsTable}(name, number, definition) values('{$func_name}', {$func['number']}, '{$func['definition']}')");
        }

    }

    public function installProductSchema()
    {
        $pTable = FCom_Catalog_Model_Product::table();
        //check if table exists
        try {
            $check_table = FCom_Catalog_Model_Product::orm()->raw_query("show tables like '{$pTable}'", null)->find_one();
            if (!$check_table){
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
        $fields = FCom_Catalog_Model_Product::orm()->raw_query("desc {$pTable}", null)->find_many();
        foreach ($fields as $f) {
            if ($f->Field == "indextank_indexed" || $f->Field == "indextank_indexed_at") {
                continue;
            }
            $doc = FCom_IndexTank_Model_ProductField::orm()->where('field_name', $f->Field)->find_one();
            if ($doc) {
                continue;
            }

            $matches = array();
            preg_match("#(\w+)#", $f->Type, $matches);
            $type = $matches[1];

            $data = array(
                'field_name'        => $f->Field,
                'field_nice_name'   => $f->Field,
                'field_type'        => $type,
                'source_type'       => 'product',
                'source_value'      => $f->Field
            );
            if (in_array($type, array('varchar', 'text'))) {
                $data['search'] = 1;
            }
            if ($f->Field == "base_price") {
                $data['scoring'] = 1;
                $data['var_number'] = 0;
            }
            if ($f->Field == "product_name") {
                $data['scoring'] = 1;
                $data['var_number'] = 1;
            }
            if ($f->Field == "manuf_sku") {
                $data['scoring'] = 1;
                $data['var_number'] = 2;
            }

            FCom_IndexTank_Model_ProductField::orm()->create($data)->save();
        }

        //price range field
        $doc = FCom_IndexTank_Model_ProductField::orm()->where('field_name', 'custom_price_range')->find_one();
        if (!$doc) {
            //add price range
            $data = array(
                    'field_name'        => 'custom_price_range',
                    'field_nice_name'   => 'Price range',
                    'field_type'        => 'text',
                    'facets'            => 1,
                    'source_type'       => 'function',
                    'source_value'      => 'fieldPriceRange'
            );
            FCom_IndexTank_Model_ProductField::orm()->create($data)->save();
        }


        //add custom fields
        $fields = FCom_CustomField_Model_Field::i()->orm()->find_many();
        if ($fields) {
            foreach ($fields as $f) {
                $fieldName = FCom_IndexTank_Index_Product::i()->getCustomFieldKey($f);
                $doc = FCom_IndexTank_Model_ProductField::orm()->where('field_name', $fieldName)->find_one();
                if ($doc) {
                    continue;
                }
                $doc = FCom_IndexTank_Model_ProductField::orm()->create();

                $matches = array();
                preg_match("#(\w+)#", $f->table_field_type, $matches);
                $type = $matches[1];

                $doc->field_name        = $fieldName;
                $doc->field_nice_name   = $f->frontend_label;
                $doc->field_type        = $type;
                $doc->facets            = 1;
                $doc->search            = 0;
                $doc->source_type       = 'product';
                $doc->source_value      = $f->field_code;

                $doc->save();
            }
        }
    }
}