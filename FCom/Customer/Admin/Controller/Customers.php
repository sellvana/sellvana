<?php

class FCom_Customer_Admin_Controller_Customers extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'customers/manage';
    protected $_gridHref = 'customers';
    protected $_gridLayoutName = '/customers';
    protected $_formLayoutName = '/customers/form';
    protected $_formViewName = 'customer/customers-form';
    protected $_modelClassName = 'FCom_Customer_Model_Customer';
    protected $_mainTableAlias = 'c';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['columns'] += array(
            'firstname' => array('label'=>'First Name'),
            'lastname' => array('label'=>'Last Name'),
            'email' => array('label'=>'Email'),
        );
        $config['custom']['dblClickHref'] = BApp::href('customers/form/?id=');
        return $config;
    }
}