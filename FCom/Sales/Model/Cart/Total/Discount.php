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
         *  - discount_amount: total discount amount
         *  - discount_percent: total cart discount percentage
         *  - details: tax info for cart, to be set as $cart->setData('discount_details', $details)
         *  - items: tax info per item
         *      - row_discount: amount of tax per item
         *      - row_discount_percent: percentage for each item
         *      - details: $item->setData('discount_details', $details)
         */

        $this->_value = !empty($result['discount_amount']) ? $result['discount_amount'] : 0;

        $cart = $this->_cart;

        if (!empty($result['items'])) {
            foreach ($cart->items() as $item) {
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

        if (!empty($result['free_items'])) {
            foreach ($result['free_items'] as $item) {

            }
        }

        $cart->set($this->_cartField, $this->_value);
        $cart->add('grand_total', -$this->_value);
        $cart->setData('discount_details', !empty($result['details']) ? $result['details'] : []);

        return $this;
    }

    public function getLabelFormatted()
    {
        $label = parent::getLabelFormatted();
        $view = $this->BLayout->view('cart/total/discount');
        if (!$view) {
            return $label;
        }
        return $view->set([
            'label' => $label,
            'cart' => $this->_cart,
            'details' => $this->_cart->getData('discount_details'),
        ])->render();
    }
}