<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Order_Comment
 *
 * @property FCom_Sales_Model_Order_Comment_State $FCom_Sales_Model_Order_Comment_State
 */
class FCom_Sales_Model_Order_Comment extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Model_Trait_OrderChild;

    static protected $_table = 'fcom_sales_order_comment';
    protected static $_origClass = __CLASS__;

    protected $_state;

    /**
     * @return FCom_Sales_Model_Order_Comment_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->FCom_Sales_Model_Order_Comment_State->factory($this);
        }
        return $this->_state;
    }

    public function __destruct()
    {
        unset($this->_order, $this->_state);
    }
}
