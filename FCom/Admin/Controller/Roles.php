<?php

class FCom_Admin_Controller_Roles extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'system/roles';
    protected $_modelClass = 'FCom_Admin_Model_Role';
    protected $_gridHref = 'roles';
    protected $_gridTitle = 'Roles and Permissions';
    protected $_recordName = 'Role';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            'role_name' => array('label'=>'Role Name', 'width'=>100),
        );
        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title' => $m->id ? 'Edit Role: '.$m->role_name : 'Create New Role',
        ));
    }

    public function formPostBefore($args)
    {
        parent::formPostBefore($args);

        if (empty($args['data']['model']['permissions'])) {
            $args['data']['model']['permissions'] = array();
        }
    }
}
