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
            'street1' => array('label'=>'Address', 'index'=>'a.street1'),
            'city' => array('label'=>'City', 'index'=>'a.city'),
            'region' => array('label'=>'Region', 'index'=>'a.region'),
            'postcode' => array('label'=>'Zip', 'index'=>'a.postcode'),
            'country' => array('label'=>'Country', 'index'=>'a.country'),
            'create_dt' => array('label'=>'Created'),
            'update_dt' => array('label'=>'Updated'),
        );
        $config['custom']['dblClickHref'] = BApp::href('customers/form/?id=');
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        $orm->left_outer_join('FCom_Customer_Model_Address', array('a.id','=','c.default_billing_id'), 'a')
            ->select(array('a.street1', 'a.city', 'a.region', 'a.postcode', 'a.country'))
        ;
    }
}