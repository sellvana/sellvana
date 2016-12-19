<?php

class FCom_AdminSPA_AdminSPA_Controller_Settings extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    public function authenticate($args = [])
    {
        return true;
    }

    public function action_grid_config()
    {
        $this->BResponse->json([]);
    }
}