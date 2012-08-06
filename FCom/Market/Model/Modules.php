<?php

class FCom_Market_Model_Modules extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_market_modules';

    public function getAllModules()
    {
        $modules = array();
        $modList = $this->orm()->find_many();
        foreach($modList as $mod) {
            $modules[$mod->mod_name] = $mod;
        }
        return $modules;
    }
}
