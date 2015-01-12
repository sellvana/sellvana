<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_Total_Tax extends FCom_Sales_Model_Cart_Total_Abstract
{
    protected $_code = 'tax';
    protected $_label = 'Tax';
    protected $_cartField = 'tax_amount';
    protected $_sortOrder = 80;

    /**
     * @return FCom_Sales_Model_Cart_Total_Tax
     */
    public function calculate()
    {
        $result = [];

        $this->BEvents->fire(__METHOD__, ['cart' => $this->_cart, 'result' => &$result]);
        /*
         * Expecting the following $result structure:
         *  - tax_amount: total tax amount
         *  - items: tax info per item
         *      - row_tax: amount of tax per item
         *      - details: $item->setData('tax_details', $details)
         *  - details: tax info for cart, to be set as $cart->setData('tax_details', $details)
         */

        $this->_value = !empty($result['tax_amount']) ? $result['tax_amount'] : 0;

        $this->_cart->set('tax_amount', $this->_value);
        $this->_cart->add('grand_total', $this->_value);
        $this->_cart->setData('tax_details', !empty($result['details']) ? $result['details'] : []);

        if (!empty($result['items'])) {
            foreach ($this->_cart->items() as $item) {
                $itemId = $item->id();
                if (!empty($result['items'][$itemId]['row_tax'])) {
                    $item->set('row_tax', $result['items'][$itemId]['row_tax']);
                } else {
                    $item->set('row_tax', 0);
                }
                if (!empty($result['items'][$itemId]['details'])) {
                    $item->setData('tax_details', $result['items'][$itemId]['details']);
                } else {
                    $item->setData('tax_details', []);
                }
            }
        }

        return $this;
    }
}