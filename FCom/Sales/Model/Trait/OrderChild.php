<?php defined('BUCKYBALL_ROOT_DIR') || die();

trait FCom_Sales_Model_Trait_OrderChild
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

    public function addHistoryEvent($type, $description, $params = null)
    {
        $orderItemId = null;
        if ($this instanceof FCom_Sales_Model_Order_Cancel) {
            $entityType = 'cancel';
        } elseif ($this instanceof FCom_Sales_Model_Order_Payment) {
            $entityType = 'payment';
        } elseif ($this instanceof FCom_Sales_Model_Order_Refund) {
            $entityType = 'refund';
        } elseif ($this instanceof FCom_Sales_Model_Order_Return) {
            $entityType = 'return';
        } elseif ($this instanceof FCom_Sales_Model_Order_Shipment) {
            $entityType = 'shipment';
        } elseif ($this instanceof FComSales_Model_Order_Item) {
            $entityType = 'order_item';
            $orderItemId = $this->id();
        }
        $history = $this->FCom_Sales_Model_Order_History->create([
            'order_id' => $this->get('order_id'),
            'entity_type' => $entityType,
            'entity_id' => $this->id(),
            'event_type' => $type,
            'event_description' => $description,
            'order_item_id' => !empty($params['order_item_id']) ? $params['order_item_id'] : $orderItemId,
            'event_at' => isset($params['event_at']) ? $params['event_at'] : $this->BDb->now(),
            'user_id' => isset($params['user_id']) ? $params['user_id'] : $this->FCom_Admin_Model_User->sessionUserId(),
        ]);
        if (isset($params['data'])) {
            $history->setData($params['data']);
        }
        $history->save();
        return $this;
    }
}
