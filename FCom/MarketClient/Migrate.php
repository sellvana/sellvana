<?php

class FCom_MarketClient_Migrate extends BClass
{
    public function install__0_2_2()
    {
        $tCoreModule = FCom_Core_Model_Module::table();
        $tMarketModule = FCom_MarketClient_Model_Module::table();
        BDb::ddlTableDef($tMarketModule, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'core_module_id' => 'int unsigned not null',
                'channel' => 'varchar(20)',
                'remote_version' => 'varchar(20)',
                'version_check_at' => 'datetime',
                'last_download_at' => 'datetime',
                'is_upgrade_available' => 'tinyint',
                'data_serialized' => 'text',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_core_module' => 'UNIQUE (core_module_id)',
                'IDX_upgrade_available' => '(is_upgrade_available)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tMarketModule}_module" => "FOREIGN KEY (core_module_id) REFERENCES {$tCoreModule} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ));
        BDb::ddlTableDef($tCoreModule, array(
            'COLUMNS' => array(
                'data_serialized' => 'DROP',
                'market_branch' => 'DROP',
                'market_version' => 'DROP',
                'market_version_check_at' => 'DROP',
                'market_download_at' => 'DROP',
                'market_upgrade_available' => 'DROP',
            ),
        ));
    }

    public function upgrade__0_2_1__0_2_2()
    {
        $tCoreModule = FCom_Core_Model_Module::table();
        BDb::ddlTableDef($tCoreModule, array(
            'COLUMNS' => array(
                'data_serialized' => 'DROP',
                'market_branch' => 'DROP',
                'market_version' => 'DROP',
                'market_version_check_at' => 'DROP',
                'market_download_at' => 'DROP',
                'market_upgrade_available' => 'DROP',
            ),
        ));
    }
}
