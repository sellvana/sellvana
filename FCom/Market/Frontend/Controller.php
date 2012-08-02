<?php

class FCom_Market_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_manifest()
    {
        $modules = BModuleRegistry::getAllModules();
        $manifest = array();
        foreach($modules as $mod) {
            $manifest[$mod->name] = $mod->version;
        }
        echo BUtil::toJson($manifest);
        exit;
    }

}