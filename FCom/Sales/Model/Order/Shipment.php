<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Order_Shipment
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Sales_Model_Order_History $FCom_Sales_Model_Order_History
 * @property FCom_Sales_Model_Order_Shipment_State $FCom_Sales_Model_Order_Shipment_State
 */

class FCom_Sales_Model_Order_Shipment extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Model_Trait_Order;

    protected static $_table = 'fcom_sales_order_shipment';
    protected static $_origClass = __CLASS__;

    protected $_state;

    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->FCom_Sales_Model_Order_Shipment_State->factory($this);
        }
        return $this->_state;
    }

    public function addHistoryEvent($type, $description, $params = null)
    {
        $history = $this->FCom_Sales_Model_Order_History->create([
            'order_id' => $this->get('order_id'),
            'entity_type' => 'shipment',
            'entity_id' => $this->id(),
            'event_type' => $type,
            'event_description' => $description,
            'event_at' => isset($params['event_at']) ? $params['event_at'] : $this->BDb->now(),
            'user_id' => isset($params['user_id']) ? $params['user_id'] : $this->FCom_Admin_Model_User->sessionUserId(),
        ]);
        if (isset($params['data'])) {
            $history->setData($params['data']);
        }
        $history->save();
        return $this;
    }

    public function __destruct()
    {
        unset($this->_order, $this->_state);
    }
}
