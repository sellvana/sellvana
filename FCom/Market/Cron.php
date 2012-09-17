<?php

class FCom_Market_Cron extends BClass
{
    public static function bootstrap()
    {
        FCom_Cron::i()
            ->task('* * * * *', 'FCom_Market_Cron.collectModules');
    }

    public function collectModules()
    {
        set_time_limit(0);

        $localModules = BModuleRegistry::i()->debug();
        $remoteModules = FCom_Market_MarketApi::i()->getModules(array_keys($localModules));


        $modulesMarket = FCom_Market_Model_Modules::i();
        foreach ($localModules as $name => $local) {
            $remote = $remoteModules[$name];
            $local->upgrade = false;
            if (false == $remote) {
                continue;
            }

            if (version_compare($remote['version'], $local->version) > 0) {
                $local->upgrade = true;
            }
            $local->market_version = $remote['version'];

            $modulesMarket->addModule($local);
        }
    }
}