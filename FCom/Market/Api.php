<?php

class FCom_Market_Api extends BClass
{
    public static function bootstrap()
    {
        //BConfig::i()->get('FCom_Market/market_url');
    }

    public function getAllModules()
    {
        //$fulleronUrl = BConfig::i()->get('FCom_Market/market_url');
        $fulleronUrl = 'http://fulleron.home/marketserver/modules';
        if (empty($fulleronUrl)) {
            return false;
        }

        $data = BUtil::fromJson(file_get_contents($fulleronUrl));
        return $data;
    }
}