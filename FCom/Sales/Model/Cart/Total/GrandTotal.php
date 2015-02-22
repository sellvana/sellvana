<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_Total_GrandTotal extends FCom_Sales_Model_Cart_Total_Abstract
{
    protected $_code = 'grand_total';
    protected $_label = 'Grand Total';
    protected $_cartField = 'grand_total';
    protected $_sortOrder = 90;

    protected $_components = [];

    /**
     * @return FCom_Sales_Model_Cart_Total_GrandTotal
     */
    public function calculate()
    {
        $cart = $this->_cart;

        $this->_value = 0;
        foreach ($this->_components as $t) {
            $this->_value += $t['value'];
        }
#var_dump($this->_components, $this->_value);
        $this->_cart->set('grand_total', $this->_value);

        if ($this->_value) {
            $cart->state()->payment()->setUnpaid();
        } else {
            $cart->state()->payment()->setFree();
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    public function addComponent($value, $type = null)
    {
        $this->_components[] = ['value' => $value, 'type' => $type];
        return $this;
    }
}