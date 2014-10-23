<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Comment extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Model_Trait_Order;

    static protected $_table = 'fcom_sales_order_comment';
    protected static $_origClass = __CLASS__;

    protected $_state;

    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->BClassRegistry->instance('FCom_Sales_Model_Order_Comment_State', true, [$this]);
        }
        return $this->_state;
    }

    public function __destruct()
    {
        unset($this->_order, $this->_state);
    }
}
