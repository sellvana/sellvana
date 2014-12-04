<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_Total_GrandTotal extends FCom_Sales_Model_Cart_Total_Abstract
{
    protected $_code = 'grand_total';
    protected $_label = 'Grand Total';
    protected $_cartField = 'grand_total';
    protected $_sortOrder = 90;

    /**
     * @return FCom_Sales_Model_Cart_Total_GrandTotal
     */
    public function calculate()
    {
        $cart = $this->_cart;
        $this->_value = $cart->get('grand_total');
        return $this;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}