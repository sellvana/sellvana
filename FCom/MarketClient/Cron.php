<?php

class FCom_MarketClient_Cron extends BClass
{
    public function collectModules()
    {
        set_time_limit(0);

        $localModules = BModuleRegistry::i()->getAllModules();
        $remoteModules = FCom_MarketClient_Main::i()->getModules(array_keys($localModules));


        $modulesMarket = FCom_MarketClient_Model_Modules::i();
        foreach ($remoteModules as $name => $remote) {
            if (empty($localModules[$name])) {
                continue;
            }
            $local = $localModules[$name];

            if (version_compare($remote['version'], $local->version) > 0) {
                $local->upgrade = true;
            }
            $local->market_version = $remote['version'];

            $modulesMarket->addModule($local);
        }
    }
}
