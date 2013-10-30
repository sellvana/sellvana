<?php

class FCom_Admin_Controller_Users extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'system/users';
    protected $_modelClass = 'FCom_Admin_Model_User';
    protected $_gridHref = 'users';
    protected $_gridTitle = 'Admin Users';
    protected $_recordName = 'User';

    public function gridConfig()
    {
        $config = parent::gridConfig();

	    $config['columns'] = array(
		    array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
		    array('name' => 'id', 'label' => 'ID', 'index' => 'id', 'width' => 55, 'cell' => 'integer'),
		    array('name' => 'username', 'label' => 'User Name', 'width' => 100, 'href' => BApp::href('users/form/?id=:id')),
		    array('name' => 'email', 'label' => 'Email', 'width' => 150),
		    array('name' => 'firstname', 'label' => 'First Name', 'width' => 150),
		    array('name' => 'lastname', 'label' => 'Last Name', 'width' => 150),
		    array('name' => 'is_superadmin', 'label' => 'Super?', 'width' => 100, 'cell' => 'integer', 'options' => FCom_Admin_Model_User::i()->fieldOptions('is_superadmin')),
		    array('name' => 'status', 'label' => 'Status', 'width' => 100, 'cell' => 'integer', 'options' => FCom_Admin_Model_User::i()->fieldOptions('status')),
	    );

        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'sidebar_img' => BUtil::gravatar($m->email),
            'title' => $m->id ? 'Edit User: '.$m->username : 'Create New User',
        ));
    }

    public function formPostBefore($args)
    {
        parent::formPostBefore($args);

        unset($args['data']['model']['password_hash']);
        if (!empty($args['data']['model']['password'])) {
            $args['model']->setPassword($args['data']['model']['password']);
        }
    }
}
