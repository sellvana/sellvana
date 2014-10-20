<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_History extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Trait_Order;

    protected static $_table = 'fcom_sales_order_history';
    protected static $_origClass = __CLASS__;

    public function __destruct()
    {
        unset($this->_order);
    }
}
