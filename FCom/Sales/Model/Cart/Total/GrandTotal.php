<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_Total_GrandTotal extends FCom_Sales_Model_Cart_Total_Abstract
{
    protected $_code = 'grand_total';
    protected $_label = 'Grand Total';
    protected $_sortOrder = 90;

    /**
     * @return FCom_Sales_Model_Cart_Total_GrandTotal
     */
    public function calculate()
    {
        $cart = $this->_cart;
        $total = $cart->subtotal;
        $total += $cart->shipping_price;
        $total += $cart->tax_amount;
        $total -= $cart->discount_amount;
        $this->_value = $cart->grand_total = $total;
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