<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Sales_Method_Checkout_Abstract extends BClass implements 
    FCom_Sales_Method_Checkout_Interface
{
    protected $_sortOrder = 50;

    public function getSortOrder()
    {
        return $this->_sortOrder;
    }
}