<?php

class FCom_MarketServer_Model_Modules extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_marketserver_modules';

    public function getAllModules()
    {
        $modules = array();
        $modList = $this->orm()->find_many();
        foreach($modList as $mod) {
            $modules[$mod->name] = $mod;
        }
        return $modules;
    }
}