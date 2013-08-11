<?php

class FCom_MarketClient_Migrate extends BClass
{
    public function install__0_2_0()
    {
        $tModules = FCom_Core_Model_Module::table();
        BDb::ddlTableDef(FCom_Core_Model_Module::table(), array(
            'COLUMNS' => array(
                'data_serialized' => 'text',
                'market_branch' => 'varchar(20)',
                'market_version' => 'varchar(20)',
                'market_version_check_at' => 'datetime',
                'market_download_at' => 'datetime',
                'market_upgrade_available' => 'tinyint',
            ),
        ));
    }

    public function upgrade__0_1_4__0_2_0()
    {
        BDb::run("DROP TABLE IF EXISTS fcom_marketclient_modules; DROP TABLE IF EXISTS fcom_market_modules");
        $this->install__0_2_0();
    }

    public function upgrade__0_2_0__0_2_1()
    {
        $tCoreModule = FCom_Core_Model_Module::table();
        $tMarketModule = FCom_MarketClient_Model_Module::table();
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

        BDb::ddlTableDef($tMarketModule, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'module_id' => 'int unsigned not null',
                'channel' => 'varchar(20)',
                'remote_version' => 'varchar(20)',
                'version_check_at' => 'datetime',
                'last_download_at' => 'datetime',
                'is_upgrade_available' => 'tinyint',
                'data_serialized' => 'text',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_module' => 'UNIQUE (module_id)',

            ),
            'CONSTRAINTS' => array(
                "FK_{$tMarketModule}_module" => 'FOREIGN KEY (module_id) REFERENCES {$tCoreModule} (id) ON UPDATE CASCADE ON DELETE CASCADE',
            ),
        ));
    }
}
