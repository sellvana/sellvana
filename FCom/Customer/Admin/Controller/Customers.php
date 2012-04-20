<?php

class FCom_Customer_Admin_Controller_Customers extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'customers';
    protected $_gridHref = 'customers';
    protected $_gridLayoutName = '/customers';
    protected $_formLayoutName = '/customers/form';
    protected $_formViewName = 'customer/customers-form';
    protected $_modelClassName = 'FCom_Customer_Model_Customer';
    protected $_mainTableAlias = 'c';

}