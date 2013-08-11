<?php

class FCom_MarketClient_Model_Module extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_marketclient_module';
    protected static $_origClass = __CLASS__;

    static public function onOrmFindOne($args)
    {

    }

    public function addModule($module)
    {
        $modName = $module->name;
        if (empty($modName)) {
            return;
        }

        $mod = $this->orm()->where('mod_name', $modName)->find_one();
        if (!$mod) {
            $mod = $this->orm()->create();
        }
        $mod->version = $module->version;
        $mod->mod_name = $modName;
        $mod->need_upgrade = !empty($module->upgrade) ? 1 : 0;
        $mod->market_version = $module->market_version;
        $mod->name = $modName;
        $mod->description = $module->description ? $module->description : $modName;
        $mod->save();
    }

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
