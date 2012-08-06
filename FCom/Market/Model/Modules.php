<?php

class FCom_Market_Model_Modules extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_market_modules';

    public function getAllModules()
    {
        $modules = array();
        $modList = $this->orm()->find_many();
        foreach($modList as $mod) {
            $modules[$mod->name] = $mod;
        }
        return $modules;
    }

    static public function install()
    {
        $tModules = FCom_Market_Model_Modules::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tModules} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `version` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }
}
