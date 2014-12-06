<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Order_Return
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Sales_Model_Order_History $FCom_Sales_Model_Order_History
 * @property FCom_Sales_Model_Order_Return_State $FCom_Sales_Model_Order_Return_State
 */

class FCom_Sales_Model_Order_Return extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_return';
    protected static $_origClass = __CLASS__;

    protected $_state;

    /**
     * @return FCom_Sales_Model_Order_Return_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->FCom_Sales_Model_Order_Return_State->factory($this);
        }
        return $this->_state;
    }

    public function __destruct()
    {
        unset($this->_order, $this->_state);
    }
}
