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
}
