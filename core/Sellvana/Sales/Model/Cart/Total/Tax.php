<?php

/**
 * Class Sellvana_Sales_Model_Cart_Total_Tax
 *
 */
class Sellvana_Sales_Model_Cart_Total_Tax extends Sellvana_Sales_Model_Cart_Total_Abstract
{
    protected $_code = 'tax';
    protected $_label = 'Tax';
    protected $_cartField = 'tax_amount';
    protected $_sortOrder = 80;

    /**
     * @return Sellvana_Sales_Model_Cart_Total_Tax
     */
    public function calculate()
    {
        $result = [];

        $this->BEvents->fire(__METHOD__, ['cart' => $this->_cart, 'result' => &$result]);
        /*
         * Expecting the following $result structure:
         *  - tax_amount: total tax amount
         *  - details: tax info for cart, to be set as $cart->setData('tax_details', $details)
         *  - items: tax info per item
         *      - row_tax: amount of tax per item
         *      - details: $item->setData('tax_details', $details)
         */

        $this->_value = !empty($result['tax_amount']) ? $result['tax_amount'] : 0;
        $this->_storeCurrencyValue = $this->_cart->convertToStoreCurrency($this->_value);

        $this->_cart->set($this->_cartField, $this->_value)
            ->setData('store_currency/' . $this->_cartField, $this->_storeCurrencyValue);
        $this->_cart->setData('tax_details', !empty($result['details']) ? $result['details'] : []);

        if (!empty($result['items'])) {
            foreach ($this->_cart->items() as $item) {
                $itemId = $item->id();
                if (!empty($result['items'][$itemId]['row_tax'])) {
                    $rowTax = $result['items'][$itemId]['row_tax'];
                } else {
                    $rowTax = 0;
                }
                $item->set('row_tax', $rowTax)
                    ->setData('store_currency/row_tax', $this->_cart->convertToStoreCurrency($rowTax));
                if (!empty($result['items'][$itemId]['details'])) {
                    $item->setData('tax_details', $result['items'][$itemId]['details']);
                } else {
                    $item->setData('tax_details', []);
                }
            }
        }

        /** @var Sellvana_Sales_Model_Cart_Total_GrandTotal $grandTotalModel */
        $grandTotalModel = $this->_cart->getTotalByType('grand_total');
        $grandTotalModel->addComponent('tax', $this->_value, $this->_storeCurrencyValue);

        return $this;
    }
}