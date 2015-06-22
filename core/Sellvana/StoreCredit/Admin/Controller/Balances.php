<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_StoreCredit_Admin_Controller_Balances extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_StoreCredit_Model_Balance';
    protected $_gridHref = 'catalog/products';
    protected $_gridTitle = 'Products';
    protected $_recordName = 'Product';
    protected $_mainTableAlias = 'p';
    protected $_permission = 'catalog/products';
    protected $_formLayoutName = '/catalog/products/form';
    protected $_formTitleField = 'product_name';

}