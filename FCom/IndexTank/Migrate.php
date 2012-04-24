<?php

class FCom_IndexTank_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        //Install IndexTank product index and functions
        FCom_IndexTank_Index_Product::i()->install();

        //create table
        $pFieldsTable = FCom_IndexTank_Model_ProductFields::table();
        BDb::run( "
            CREATE TABLE IF NOT EXISTS {$pFieldsTable} (
            `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
            `field_name` varchar(1024) NOT NULL DEFAULT '',
            `field_nice_name` varchar(1024) NOT NULL DEFAULT '',
            `field_type`    varchar(255) not null default '',
            `search` tinyint(1) not null default 0,
            `facets` tinyint(1) not null default 0,
            `sorting` tinyint(1) not null default 0,
            `var_number` tinyint(3) not null default -1,
            `priority` int(11) unsigned NOT NULL DEFAULT '1',
            `show` enum('','link','checkbox') NOT NULL DEFAULT '',
            `filter` enum('','inclusive','exclusive') NOT NULL DEFAULT '',
            `source_type` varchar(255) NOT NULL,
            `source_value` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
            )ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->installProductSchema();
    }

    public function installProductSchema()
    {
        $pTable = FCom_Catalog_Model_Product::table();
        $fields = FCom_Catalog_Model_Product::orm()->raw_query("desc {$pTable}", null)->find_many();
        foreach($fields as $f){
            $doc = FCom_IndexTank_Model_ProductFields::orm()->where('field_name', $f->Field)->find_one();
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
                    $data['sorting'] = 1;
                    $data['var_number'] = 0;
                }
            } else {
                continue;
            }

            FCom_IndexTank_Model_ProductFields::orm()->create($data)->save();

        }

        $doc = FCom_IndexTank_Model_ProductFields::orm()->where('field_name', 'custom_price_range')->find_one();
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
            FCom_IndexTank_Model_ProductFields::orm()->create($data)->save();
        }

        $doc = FCom_IndexTank_Model_ProductFields::orm()->where('field_name', 'ct_categories')->find_one();
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
            FCom_IndexTank_Model_ProductFields::orm()->create($data)->save();
        }

        //add custom fields
        $fields = FCom_CustomField_Model_Field::i()->orm()->find_many();
        if ($fields){
            foreach($fields as $f){
                $field_name = 'cf_'.$f->field_type.'___'.$f->field_code;
                $doc = FCom_IndexTank_Model_ProductFields::orm()->where('field_name', $field_name)->find_one();
                if ($doc){
                    continue;
                }
                $doc = FCom_IndexTank_Model_ProductFields::orm()->create();

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