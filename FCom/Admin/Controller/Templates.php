<?php

class FCom_Admin_Controller_Templates extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'system/templates';
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'templates';
    protected $_gridTitle = 'Frontend Templates';
    protected $_recordName = 'Template';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40, 'overflow' => true),
            //array('name' => 'id', 'label' => 'ID', 'index' => 'm.id', 'width' => 55, 'hidden' => true, 'cell' => 'integer'),
            array('name' => 'view_name', 'label' => 'View Name', 'index' => 'view_name', 'width' => 100, 'overflow' => true),
        );

        $config['data'] = array(
            array('view_name' => '/static/index'),
        );
        $config['data_mode'] = 'local';
        $config['filters'] = array(
            array('field' => 'name', 'type' => 'text'),
            array('field' => 'run_level_core', 'type' => 'multiselect')
        );
        $config['actions'] = array(
            'edit' => true
        );
        $config['events'] = array('edit', 'mass-edit');

        //$config['state'] =array(5,6,7,8);
        return $config;
    }
}
