<?php

class FCom_IndexTank_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        return false; //TODO skip if no configuration

        //create product index
        FCom_IndexTank_Index_Product::i()->install();

        $pIndexHelperTable = FCom_IndexTank_Model_IndexHelper::table();
        BDb::run( "
            CREATE TABLE {$pIndexHelperTable} (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `index` VARCHAR( 255 ) NOT NULL ,
            `checkpoint` TIMESTAMP 
            ) ENGINE = InnoDB;
         ");
        BDb::run("insert into {$pIndexHelperTable}(`index`, checkpoint) values('products', null");

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
            `show` enum('','link','checkbox') NOT NULL DEFAULT '',
            `filter` enum('','inclusive','exclusive') NOT NULL DEFAULT '',
            `source_type` varchar(255) NOT NULL,
            `source_value` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
            )ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $pFunctionsTable = FCom_IndexTank_Model_ProductFunction::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$pFunctionsTable} (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `name` VARCHAR( 1024 ) NOT NULL,
            `number` INT( 11 ) UNSIGNED NOT NULL ,
            `definition` VARCHAR( 1024 ) NOT NULL
            ) ENGINE = InnoDB;
            ");
        //predefined functions
        $functions  =  array (
                'age'                   => array('number' => 0, 'definition' => '-age'         ),
                'relevance'             => array('number' => 1, 'definition' => 'relevance'    ),
                'base_price_asc'        => array('number' => 2, 'definition' => '-d[0]'  ),
                'base_price_desc'       => array('number' => 3, 'definition' => 'd[0]'   )
        );
        //insert predefined functions
        $functions_list = FCom_IndexTank_Model_ProductFunction::i()->get_list();
        foreach($functions as $func_name => $func){
            //add new function only if function not exists yet
            if(!empty($functions_list[$func['number']])){
                continue;
            }
            BDb::run("insert into {$pFunctionsTable}(name, number, definition) values('{$func_name}', {$func['number']}, '{$func['definition']}')");
            //add functions to index
            FCom_IndexTank_Index_Product::i()->update_function($func['number'], $func['definition']);
        }

        //setup basic index schema
        $this->installProductSchema();
    }

    public function installProductSchema()
    {
        $pTable = FCom_Catalog_Model_Product::table();
        $fields = FCom_Catalog_Model_Product::orm()->raw_query("desc {$pTable}", null)->find_many();
        foreach($fields as $f){
            $doc = FCom_IndexTank_Model_ProductField::orm()->where('field_name', $f->Field)->find_one();
            if($doc){
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
            if ( in_array($type, array('varchar', 'text')) ){
                $data['search'] = 1;
            } else if ( in_array($type, array('decimal', 'timestamp')) ) {
                if("base_price" == $f->Field){
                    $data['scoring'] = 1;
                    $data['var_number'] = 0;
                }
            } else {
                continue;
            }

            FCom_IndexTank_Model_ProductField::orm()->create($data)->save();

        }


        $doc = FCom_IndexTank_Model_ProductField::orm()->where('field_name', 'custom_price_range')->find_one();
        if (!$doc){
            //add price range
            $data = array(
                    'field_name'        => 'custom_price_range',
                    'field_nice_name'   => 'Price range',
                    'field_type'        => 'text',
                    'facets'            => 1,
                    'source_type'       => 'function',
                    'source_value'      => 'price_range_large'
            );
            FCom_IndexTank_Model_ProductField::orm()->create($data)->save();
        }

        $doc = FCom_IndexTank_Model_ProductField::orm()->where('field_name', 'ct_categories___')->find_one();
        if (!$doc){
            //add categories
            $data = array(
                    'field_name'        => 'ct_categories___',
                    'field_nice_name'   => 'Categories',
                    'field_type'        => 'text',
                    'search'            => 1,
                    'facets'            => 1,
                    'source_type'       => 'function',
                    'source_value'      => 'get_categories'
            );
            FCom_IndexTank_Model_ProductField::orm()->create($data)->save();
        }

        //add custom fields
        $fields = FCom_CustomField_Model_Field::i()->orm()->find_many();
        if ($fields){
            foreach($fields as $f){
                $field_name = FCom_IndexTank_Index_Product::i()->get_custom_field_key($f);
                $doc = FCom_IndexTank_Model_ProductField::orm()->where('field_name', $field_name)->find_one();
                if ($doc){
                    continue;
                }
                $doc = FCom_IndexTank_Model_ProductField::orm()->create();

                $matches = array();
                preg_match("#(\w+)#", $f->table_field_type, $matches);
                $type = $matches[1];

                $doc->field_name        = $field_name;
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