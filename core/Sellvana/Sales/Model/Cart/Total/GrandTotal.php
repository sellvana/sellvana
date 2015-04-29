<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Cart_Total_GrandTotal extends Sellvana_Sales_Model_Cart_Total_Abstract
{
    protected $_code = 'grand_total';
    protected $_label = 'Grand Total';
    protected $_cartField = 'grand_total';
    protected $_sortOrder = 90;

    protected $_components = [];

    /**
     * @return Sellvana_Sales_Model_Cart_Total_GrandTotal
     */
    public function calculate()
    {
        $cart = $this->_cart;

        $this->_value = 0;
        $this->_storeCurrencyValue = 0;
        foreach ($this->_components as $t) {
            $this->_value += $t['value'];
            $this->_storeCurrencyValue += $t['store_currency_value'];
        }
#var_dump($this->_components, $this->_value);
        $this->_cart->set('grand_total', $this->_value);
        $this->_cart->setData('store_currency/grand_total', $this->_storeCurrencyValue);

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

    public function resetComponents($components = [])
    {
        $this->_components = $components;
        return $this;
    }

    public function addComponent($type, $value, $storeCurrencyValue = null)
    {
        $this->_components[] = ['type' => $type, 'value' => $value, 'store_currency_value' => $storeCurrencyValue ?: $value];
        return $this;
    }
}