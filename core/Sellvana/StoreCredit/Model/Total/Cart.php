<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_StoreCredit_Model_Total_Cart
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */
class Sellvana_StoreCredit_Model_Total_Cart extends Sellvana_Sales_Model_Cart_Total_Abstract
{
    protected $_code      = 'store_credit';
    protected $_label     = 'Store Credit';
    protected $_cartField = 'store_credit';
    protected $_sortOrder = 110;

    /**
     * @return Sellvana_StoreCredit_Model_Total_Cart
     */
    public function calculate()
    {
        $this->_value = $this->getValue();
        $this->_storeCurrencyValue = $this->_cart->convertToStoreCurrency($this->_value);
        $this->_cart->setData('store_currency/store_credit', $this->_storeCurrencyValue);

        return $this;
    }

    public function onAmountDueCalculate($args)
    {
        $this->_cart = $args['cart'];
        $args['amount_due'] -= $this->getValue();
    }

    public function getValue($calculated = false)
    {
        $use = $this->_cart->getData('store_credit/use');
        $amount = $this->_cart->getData('store_credit/amount');
        $amountCurrency = $this->_cart->getData('store_credit/amount_currency');
        if ($use && $amountCurrency) {
            $amount = $amountCurrency / $this->_cart->getCurrencyRate();
            $this->_cart->setData('store_credit/amount', $amount);
        }
        return (float)$amount;
    }
}