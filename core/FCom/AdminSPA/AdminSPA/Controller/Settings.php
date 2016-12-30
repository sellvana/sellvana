<?php

class FCom_AdminSPA_AdminSPA_Controller_Settings extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    public function authenticate($args = [])
    {
        return true;
    }

    public function action_config()
    {
        $result = [];

        $this->layout('sv-app-setings-config');
        /** @var FCom_AdminSPA_AdminSPA_View_App $appView */
        $appView = $this->view('app');
        $navTree = $appView->normalizeSettingsNav()->getNavTree();

        $result['nav'] = $navTree;

        $this->respond($result);
    }

    public function action_data()
    {
        $result = [];//$this->BConfig->get();

        $this->respond($result);
    }
}