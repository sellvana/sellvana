<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Item extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Trait_Order;

    protected static $_table = 'fcom_sales_order_item';
    protected static $_origClass = __CLASS__;

    protected $_state;

    /**
    * Fallback singleton/instance factory
    *
    * @param bool $new if true returns a new instance, otherwise singleton
    * @param array $args
    * @return FCom_Sales_Model_Order_Item
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(get_called_class(), $args, !$new);
    }

    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->BClassRegistry->instance('FCom_Sales_Model_Order_Item_State', true, [$this]);
        }
        return $this->_state;
    }

    public function addHistoryEvent($type, $description, $params = null)
    {
        $history = $this->FCom_Sales_Model_Order_History->create([
            'order_id' => $this->get('order_id'),
            'order_item_id' => $this->id(),
            'entity_type' => 'order_item',
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

    public function addNew($data)
    {
        $this->BEvents->fire(__CLASS__ . '.addNew', ['orderItem' => $data]);
        return $this->create($data)->save();
    }

    public function update($data)
    {
        $this->BEvents->fire(__CLASS__ . '.update', ['orderItem' => $data]);
        return $this->set($data)->save();
    }

    public function isItemExist($orderId, $product_id)
    {
        return $this->orm()->where("order_id", $orderId)
                        ->where("product_id", $product_id)->find_one();
    }

    public function __destruct()
    {
        unset($this->_state, $this->_order);
    }
}
