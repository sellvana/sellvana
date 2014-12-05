<?php defined('BUCKYBALL_ROOT_DIR') || die();

trait FCom_Sales_Model_Trait_Order
{
    protected $_order;

    /**
     * @param FCom_Sales_Model_Order $order
     * @return FCom_Sales_Model_Order
     */
    public function order(FCom_Sales_Model_Order $order = null)
    {
        if (!empty($order)) {
            $this->_order = $order;
        } elseif (!$this->_order && $this->get('order_id')) {
            $this->_order = $this->FCom_Sales_Model_Order->load($this->get('order_id'));
        }
        return $this->_order;
    }
}
