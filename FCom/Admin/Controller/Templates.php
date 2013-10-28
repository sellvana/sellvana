<?php

class FCom_Admin_Controller_Templates extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'templates';
    protected $_gridTitle = 'Frontend Templates';
    protected $_recordName = 'Template';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['columns'] = array(
            'view_name' => array('title' => 'Template Path'),
        );

        return $config;
    }
}
