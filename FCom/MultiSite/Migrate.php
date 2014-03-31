<?php

class FCom_MultiSite_Migrate extends BClass
{
    public function install__0_1_1()
    {
        $tSite = FCom_MultiSite_Model_Site::table();

        BDb::ddlTableDef($tSite, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'name' => 'varchar(100) not null',
                'match_domains' => 'text',
                'default_theme' => 'varchar(100)',
                'layout_update' => 'text',
                'root_category_id' => 'int unsigned',
                'mode_by_ip' => 'text',
                'meta_title' => 'text',
                'meta_description' => 'text',
                'meta_keywords' => 'text',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_name' => '(name)',
            ),
        ));
    }
    public function upgrade__0_1_0__0_1_1()
    {
        $table = FCom_MultiSite_Model_Site::table();
        BDb::ddlTableDef($table, array(
            'COLUMNS' => array(
                  'create_dt'      => 'RENAME create_at datetime NOT NULL',
                  'update_dt'      => 'RENAME update_at datetime NOT NULL',
            ),
        ));
    }
}
