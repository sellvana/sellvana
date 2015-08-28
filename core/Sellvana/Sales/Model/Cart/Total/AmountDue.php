<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Model_Cart_Total_AmountDue
 *
 * @property Sellvana_MultiCurrency_Main $Sellvana_MultiCurrency_Main
 */
class Sellvana_Sales_Model_Cart_Total_AmountDue extends Sellvana_Sales_Model_Cart_Total_Abstract
{
    protected $_code      = 'amount_due';
    protected $_label     = 'Balance Due';
    protected $_cartField = 'amount_due';
    protected $_sortOrder = 200;

    /**
     * @return Sellvana_Sales_Model_Cart_Total_AmountDue
     */
    public function calculate()
    {
        $cart = $this->_cart;

        $grandTotal = $cart->get('grand_total');
        $this->_value = $grandTotal;

        $this->BEvents->fire(__METHOD__, ['cart' => $cart, 'amount_due' => &$this->_value]);

        $this->_cart->set('amount_due', $this->_value);
        $this->_cart->set('amount_paid', $grandTotal - $this->_value);

        $this->_storeCurrencyValue = $this->_cart->convertToStoreCurrency($this->_value);
        $this->_cart->setData('store_currency/amount_due', $this->_storeCurrencyValue);

        if ($this->_value) {
            $cart->state()->payment()->setUnpaid();
        } elseif ($grandTotal) {
            $cart->state()->payment()->setPaid();
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
}
