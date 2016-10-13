<?php

/**
 * Class FCom_Core_Controller_Main
 *
 */
class FCom_Core_Controller_Main extends FCom_Core_Controller_Abstract
{
    public function action_initialize_js()
    {
        $this->layout('/initialize.js');
    }

    public function action_initialize_css()
    {
        $this->layout('/initialize.css');
    }
}