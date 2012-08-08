<?php

class FCom_Market_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_modules()
    {
        $modules = FCom_Market_Model_Modules::orm()->find_many();
        $manifest = array();
        foreach($modules as $mod) {
            $manifest[$mod->name] = $mod->version;
        }
        echo BUtil::toJson($manifest);
        exit;
    }

}