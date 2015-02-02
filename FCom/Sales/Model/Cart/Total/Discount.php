<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_Total_Discount extends FCom_Sales_Model_Cart_Total_Abstract
{
    protected $_label = 'Discount';
    protected $_cartField = 'discount_amount';
    protected $_sortOrder = 70;

    /**
     * @return FCom_Sales_Model_Cart_Total_Discount
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

        $this->_value = !empty($result['discount_amount']) ? $result['discount_amount'] : 0;
        $this->_cart->set($this->_cartField, $this->_value);
        $this->_cart->add('grand_total', -$this->_value);
        $this->_cart->setData('discount_details', !empty($result['details']) ? $result['details'] : []);

        if (!empty($result['items'])) {
            foreach ($this->_cart->items() as $item) {
                $itemId = $item->id();
                if (!empty($result['items'][$itemId]['row_discount'])) {
                    $item->set('row_discount', $result['items'][$itemId]['row_discount']);
                } else {
                    $item->set('row_discount', 0);
                }
                if (!empty($result['items'][$itemId]['details'])) {
                    $item->setData('discount_details', $result['items'][$itemId]['details']);
                } else {
                    $item->setData('discount_details', []);
                }
            }
        }

        return $this;
    }
}