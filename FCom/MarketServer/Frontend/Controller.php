<?php

class FCom_MarketServer_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_market()
    {
        $modules = BModuleRegistry::getAllModules();
        //todo: filter only public modules
        //show modules and description
        $this->view('market/list')->modules = $modules;
        $this->layout('/market/list');
    }
}