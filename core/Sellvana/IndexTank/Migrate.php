<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_IndexTank_Migrate
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_CustomField_Model_Field $Sellvana_CustomField_Model_Field
 * @property Sellvana_IndexTank_Index_Product $Sellvana_IndexTank_Index_Product
 * @property Sellvana_IndexTank_Model_IndexHelper $Sellvana_IndexTank_Model_IndexHelper
 * @property Sellvana_IndexTank_Model_IndexingStatus $Sellvana_IndexTank_Model_IndexingStatus
 * @property Sellvana_IndexTank_Model_ProductField $Sellvana_IndexTank_Model_ProductField
 * @property Sellvana_IndexTank_Model_ProductFunction $Sellvana_IndexTank_Model_ProductFunction
 */

class Sellvana_IndexTank_Migrate extends BClass
{
    public function install__0_2_1()
    {
        $pIndexHelperTable = $this->Sellvana_IndexTank_Model_IndexHelper->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$pIndexHelperTable} (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `index` VARCHAR( 255 ) NOT NULL ,
            `checkpoint` TIMESTAMP
            ) ENGINE = InnoDB;
         ");
        $this->BDb->run("insert into {$pIndexHelperTable} (`index`, checkpoint) values('products', null)");

        //create table
        $pFieldsTable = $this->Sellvana_IndexTank_Model_ProductField->table();
        $this->BDb->run("
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
            `sort_order` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
            )ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->BDb->ddlClearCache();

        //add initial data
        $this->installProductSchema();

        //create table
        $pFunctionsTable = $this->Sellvana_IndexTank_Model_ProductFunction->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$pFunctionsTable} (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `name` varchar(1024)  NOT NULL,
              `number` int(11) NOT NULL DEFAULT '-1',
              `definition` varchar(1024)  NOT NULL,
              `label` varchar(100)  NOT NULL,
              `field_name` varchar(100) NOT NULL,
              `sort_order` enum('asc','desc') NOT NULL DEFAULT 'asc',
              `use_custom_formula` tinyint(1) NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`)
            ) ENGINE = InnoDB;
            ");
        $this->BDb->ddlClearCache();

        //predefined functions
        $functions  =  [
                'age'                   => ['number' => 0, 'definition' => '-age'         ],
                'relevance'             => ['number' => 1, 'definition' => 'relevance'    ],
                'base_price_asc'        => ['number' => 2, 'definition' => '-d[0]'  ],
                'base_price_desc'       => ['number' => 3, 'definition' => 'd[0]'   ],
                'product_name_asc'        => ['number' => 4, 'definition' => '-d[1]'  ],
                'product_name_desc'       => ['number' => 5, 'definition' => 'd[1]'   ],
                'product_sku_asc'        => ['number' => 6, 'definition' => '-d[2]'  ],
                'product_sku_desc'       => ['number' => 7, 'definition' => 'd[2]'   ],
        ];
        $functionsList = $this->Sellvana_IndexTank_Model_ProductFunction->getList();
        //add initial functions
        foreach ($functions as $func_name => $func) {
            //add new function only if function not exists yet
            if (!empty($functionsList[$func['number']])) {
                continue;
            }
            $this->BDb->run("insert into {$pFunctionsTable}(name, number, definition) values('{$func_name}', {$func['number']}, '{$func['definition']}')");
        }

        $productsTable = $this->Sellvana_Catalog_Model_Product->table();
        if (!$this->BDb->ddlFieldInfo($productsTable, 'indextank_indexed')) {
            $this->BDb->run(" ALTER TABLE {$productsTable} ADD indextank_indexed tinyint(1) not null default 0,
            ADD indextank_indexed_at datetime not null; ");
        }

        $pIndexingStatusTable = $this->Sellvana_IndexTank_Model_IndexingStatus->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$pIndexingStatusTable} (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `task` VARCHAR( 255 ) NOT NULL ,
            `info` VARCHAR( 255 ) NOT NULL ,
            `updated_at` datetime
            ) ENGINE = InnoDB;
         ");
        $pIndexingStatusTable = $this->Sellvana_IndexTank_Model_IndexingStatus->table();
        $this->BDb->ddlTableDef($pIndexingStatusTable, [BDb::COLUMNS => [
            'status' => "enum('start', 'pause') NOT NULL DEFAULT 'start'",
            'percent' => "BIGINT( 11 ) NOT NULL",
            'indexed' => "BIGINT( 11 ) NOT NULL",
            'to_index' => "BIGINT( 11 ) NOT NULL",
            'index_size' => "BIGINT( 11 ) NOT NULL",
            'label' => "varchar(100) NOT NULL"

        ]]);
        $sql = "
        update {$pFunctionsTable} set label = 'Newest first' where name='age';
        update {$pFunctionsTable} set label = 'Relevance' where name='relevance';
        update {$pFunctionsTable} set label = 'Price (Lower first)' where name='base_price_asc';
        update {$pFunctionsTable} set label = 'Price (Higher first)' where name='base_price_desc';
        update {$pFunctionsTable} set label = 'Product name (A-Z)' where name='product_name_asc';
        update {$pFunctionsTable} set label = 'Product name (Z-A)' where name='product_name_desc';
        update {$pFunctionsTable} set label = 'Manuf SKU (A-Z)' where name='product_sku_asc';
        update {$pFunctionsTable} set label = 'Manuf SKU (Z-A)' where name='product_sku_desc';
        ";
        $this->BDb->run($sql);

    }

    public function installProductSchema()
    {
        $pTable = $this->Sellvana_Catalog_Model_Product->table();
        //check if table exists
        try {
            $check_table = $this->Sellvana_Catalog_Model_Product->orm()->raw_query("show tables like '{$pTable}'", null)->find_one();
            if (!$check_table) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
        $fields = $this->Sellvana_Catalog_Model_Product->orm()->raw_query("desc {$pTable}", null)->find_many();
        foreach ($fields as $f) {
            if ($f->Field == "indextank_indexed" || $f->Field == "indextank_indexed_at") {
                continue;
            }
            $doc = $this->Sellvana_IndexTank_Model_ProductField->orm()->where('field_name', $f->Field)->find_one();
            if ($doc) {
                continue;
            }

            $matches = [];
            preg_match("#(\w+)#", $f->Type, $matches);
            $type = $matches[1];

            $data = [
                'field_name'        => $f->Field,
                'field_nice_name'   => $f->Field,
                'field_type'        => $type,
                'source_type'       => 'product',
                'source_value'      => $f->Field
            ];
            if (in_array($type, ['varchar', 'text'])) {
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
            if ($f->Field == "product_sku") {
                $data['scoring'] = 1;
                $data['var_number'] = 2;
            }

            $this->Sellvana_IndexTank_Model_ProductField->create($data)->save();
        }

        //price range field
        $doc = $this->Sellvana_IndexTank_Model_ProductField->orm()->where('field_name', 'custom_price_range')->find_one();
        if (!$doc) {
            //add price range
            $data = [
                    'field_name'        => 'custom_price_range',
                    'field_nice_name'   => 'Price range',
                    'field_type'        => 'text',
                    'facets'            => 1,
                    'source_type'       => 'function',
                    'source_value'      => 'fieldPriceRange'
            ];
            $this->Sellvana_IndexTank_Model_ProductField->create($data)->save();
        }


        //add custom fields
        $fields = $this->Sellvana_CustomField_Model_Field->orm()->find_many();
        if ($fields) {
            foreach ($fields as $f) {
                $fieldName = $this->Sellvana_IndexTank_Index_Product->getCustomFieldKey($f);
                $doc = $this->Sellvana_IndexTank_Model_ProductField->orm()->where('field_name', $fieldName)->find_one();
                if ($doc) {
                    continue;
                }
                $doc = $this->Sellvana_IndexTank_Model_ProductField->create();

                $matches = [];
                preg_match("#(\w+)#", $f->table_field_type, $matches);
                $type = $matches[1];

                $doc->field_name        = $fieldName;
                $doc->field_nice_name   = $f->frontend_label;
                $doc->field_type        = $type;
                $doc->facets            = 1;
                $doc->search            = 0;
                $doc->source_type       = 'custom_field';
                $doc->source_value      = $f->field_code;

                $doc->save();
            }
        }
    }

    public function uninstall()
    {
        $productsTable = $this->Sellvana_Catalog_Model_Product->table();
        $this->BDb->run(" ALTER TABLE {$productsTable} DROP indextank_indexed,
        DROP indextank_indexed_at; ");

        $pIndexHelperTable = $this->Sellvana_IndexTank_Model_IndexHelper->table();
        $this->BDb->run(" DROP TABLE {$pIndexHelperTable}; ");

        $pFieldsTable = $this->Sellvana_IndexTank_Model_ProductField->table();
        $this->BDb->run(" DROP TABLE {$pFieldsTable}; ");

        $pFunctionsTable = $this->Sellvana_IndexTank_Model_ProductFunction->table();
        $this->BDb->run(" DROP TABLE {$pFunctionsTable}; ");

    }

    public function upgrade__0_1_0__0_1_1()
    {
        $productsTable = $this->Sellvana_Catalog_Model_Product->table();
        if (!$this->BDb->ddlFieldInfo($productsTable, 'indextank_indexed')) {
            $this->BDb->run(" ALTER TABLE {$productsTable} ADD indextank_indexed tinyint(1) not null default 0,
            ADD indextank_indexed_at datetime not null; ");
        }

        $pIndexingStatusTable = $this->Sellvana_IndexTank_Model_IndexingStatus->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$pIndexingStatusTable} (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `task` VARCHAR( 255 ) NOT NULL ,
            `info` VARCHAR( 255 ) NOT NULL ,
            `updated_at` datetime
            ) ENGINE = InnoDB;
         ");

    }

    public function upgrade__0_1_1__0_1_2()
    {
        $pFunctionsTable = $this->Sellvana_IndexTank_Model_ProductFunction->table();
        $this->BDb->run(" ALTER TABLE {$pFunctionsTable} MODIFY `number` int(11) NOT NULL DEFAULT '-1'");
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $productsTable = $this->Sellvana_Catalog_Model_Product->table();
        if (!$this->BDb->ddlFieldInfo($productsTable, 'indextank_indexed')) {
            $this->BDb->run(" ALTER TABLE {$productsTable} ADD INDEX (indextank_indexed); ");
        }
    }

    public function upgrade__0_1_3__0_1_4()
    {
        $pIndexingStatusTable = $this->Sellvana_IndexTank_Model_IndexingStatus->table();
        $this->BDb->ddlTableDef($pIndexingStatusTable, [BDb::COLUMNS => [
            'status' => "enum('start','stop','pause') NOT NULL",
            'percent' => "BIGINT( 11 ) NOT NULL",
            'indexed' => "BIGINT( 11 ) NOT NULL",
        ]]);
//        $this->BDb->run( " ALTER TABLE {$pIndexingStatusTable}
//        ADD `status` enum('start','stop','pause') NOT NULL,
//        ADD `percent` BIGINT( 11 ) NOT NULL ,
//        ADD `indexed` BIGINT( 11 ) NOT NULL ; ");
    }

    public function upgrade__0_1_4__0_1_5()
    {
        $pIndexingStatusTable = $this->Sellvana_IndexTank_Model_IndexingStatus->table();
        $this->BDb->run(" ALTER TABLE {$pIndexingStatusTable}
        MODIFY `status` ENUM( 'start', 'pause' ) NOT NULL DEFAULT 'start'; ");
    }

    public function upgrade__0_1_5__0_1_6()
    {
        $pIndexingStatusTable = $this->Sellvana_IndexTank_Model_IndexingStatus->table();
        $this->BDb->ddlTableDef($pIndexingStatusTable, [BDb::COLUMNS => ['to_index' => "BIGINT( 11 ) NOT NULL"]]);
//        $this->BDb->run( " ALTER TABLE {$pIndexingStatusTable} ADD `to_index` BIGINT( 11 ) NOT NULL ;");
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $pIndexingStatusTable = $this->Sellvana_IndexTank_Model_IndexingStatus->table();
        $this->BDb->ddlTableDef($pIndexingStatusTable, [BDb::COLUMNS => ['index_size' => "BIGINT( 11 ) NOT NULL"]]);
//        $this->BDb->run( " ALTER TABLE {$pIndexingStatusTable} ADD `index_size` BIGINT( 11 ) NOT NULL ;");
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $pPFTable = $this->Sellvana_IndexTank_Model_ProductFunction->table();
        $this->BDb->ddlTableDef($pPFTable, [BDb::COLUMNS => ['label' => "varchar(100) NOT NULL"]]);
//        $this->BDb->run( " ALTER TABLE {$pPFTable} ADD `label` varchar(100) NOT NULL ;");
    }

    public function upgrade__0_1_8__0_1_9()
    {
        $pPFTable = $this->Sellvana_IndexTank_Model_ProductFunction->table();
        $sql = "
        update {$pPFTable} set label = 'Newest first' where name='age';
        update {$pPFTable} set label = 'Relevance' where name='relevance';
        update {$pPFTable} set label = 'Price (Lower first)' where name='base_price_asc';
        update {$pPFTable} set label = 'Price (Higher first)' where name='base_price_desc';
        update {$pPFTable} set label = 'Product name (A-Z)' where name='product_name_asc';
        update {$pPFTable} set label = 'Product name (Z-A)' where name='product_name_desc';
        update {$pPFTable} set label = 'Manuf SKU (A-Z)' where name='product_sku_asc';
        update {$pPFTable} set label = 'Manuf SKU (Z-A)' where name='product_sku_desc';
        ";
        $this->BDb->run($sql);
    }

    public function upgrade__0_1_9__0_2_0()
    {
        $pPFTable = $this->Sellvana_IndexTank_Model_ProductFunction->table();
        $this->BDb->ddlTableColumns($pPFTable, [
            'field_name' => "varchar(100) NOT NULL",
            'sort_order' => "enum('asc','desc') NOT NULL DEFAULT 'asc'",
            'use_custom_formula' => "tinyint(1) NOT NULL DEFAULT 0",
        ]);
    }

    public function upgrade__0_2_0__0_2_1()
    {
        $pFieldsTable = $this->Sellvana_IndexTank_Model_ProductField->table();
        $this->BDb->ddlTableDef($pFieldsTable, [BDb::COLUMNS => ['sort_order' => "int(11) NOT NULL DEFAULT '0'"]]);
//        $this->BDb->run( " ALTER TABLE {$pFieldsTable} ADD `sort_order` int(11) NOT NULL DEFAULT '0'");
    }
}
