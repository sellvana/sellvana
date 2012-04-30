<?php

class FCom_Admin_Controller_Users extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'admin/users';
    protected $_modelClass = 'FCom_Admin_Model_User';
    protected $_gridHref = 'users';
    protected $_gridTitle = 'Admin Users';
    protected $_recordName = 'User';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['columns'] += array(
            'username'    => array('label'=>'User Name', 'width'=>100),
            'email'       => array('label'=>'Email', 'width'=>150),
            'firstname'   => array('label'=>'First Name', 'width'=>150),
            'lastname'    => array('label'=>'First Name', 'width'=>150),
            'is_superadmin' => array('label'=>'Super?', 'width'=>100,
                'options'=>FCom_Admin_Model_User::i()->fieldOptions('is_superadmin')),
            'status'      => array('label'=>'Status', 'width'=>100,
                'options'=>FCom_Admin_Model_User::i()->fieldOptions('status')),
            'last_login ' => array('label'=>'Last Login', 'formatter'=>'date', 'width'=>100),
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

        unset($data['model']['password_hash']);
        if (!empty($data['model']['password'])) {
            $model->setPassword($data['model']['password']);
        }
    }
}