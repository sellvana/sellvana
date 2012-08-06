<?php

class FCom_MarketServer_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        FCom_MarketServer_Model_Account::install();
        FCom_MarketServer_Model_Modules::install();
    }

}