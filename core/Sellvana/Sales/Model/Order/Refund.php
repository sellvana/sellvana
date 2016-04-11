<?php

/**
 * Class Sellvana_Sales_Model_Order_Refund
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 * @property Sellvana_Sales_Model_Order_Refund_State $Sellvana_Sales_Model_Order_Refund_State
 * @property Sellvana_Sales_Model_Order_Refund_Item $Sellvana_Sales_Model_Order_Refund_Item
 */

class Sellvana_Sales_Model_Order_Refund extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_refund';
    protected static $_origClass = __CLASS__;

    protected $_state;

    /**
     * @refund Sellvana_Sales_Model_Order_Refund_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->Sellvana_Sales_Model_Order_Refund_State->factory($this);
        }
        return $this->_state;
    }

    public function importFromOrder(Sellvana_Sales_Model_Order $order, array $qtys = null)
    {
        $this->order($order);
        $this->state()->overall()->setDefaultState();
        $this->state()->custom()->setDefaultState();
        $this->save();

        $items = $order->items();
        if ($qtys === null) {
            $qtys = [];
            foreach ($items as $item) {
                $qtys[$item->id()] = true;
            }
        }

        foreach ($qtys as $itemId => $qty) {
            if (empty($items[$itemId])) {
                throw new BException($this->_('Invalid item id: %s', $itemId));
            }
            /** @var Sellvana_Sales_Model_Order_Item $item */
            $item = $items[$itemId];
            $qtyCanRefund = $item->getQtyCanRefund();
            if ($qty === true) {
                $qty = $qtyCanRefund;
            } elseif ($qty <= 0 || $qty > $qtyCanRefund) {
                throw new BException($this->_('Invalid quantity to refund for %s: %s', [$item->get('product_sku'), $qty]));
            }
            $this->Sellvana_Sales_Model_Order_Refund_Item->create([
                'order_id' => $order->id(),
                'refund_id' => $this->id(),
                'order_item_id' => $item->id(),
                'qty' => $qty,
            ])->save();
        }

        return $this;
    }
    
    public function register()
    {
        $order = $this->order();
        $orderItems = $order->items();
        $refundItems = $this->items();

        foreach ($refundItems as $cItem) {
            $oItem = $orderItems[$cItem->get('order_item_id')];
            $oItem->add('qty_refunded', $cItem->get('qty'));
        }

        return $this;
    }

    public function unregister()
    {
        $order = $this->order();
        $orderItems = $order->items();
        $refundItems = $this->items();

        foreach ($refundItems as $cItem) {
            $oItem = $orderItems[$cItem->get('order_item_id')];
            $oItem->add('qty_refunded', -$cItem->get('qty'));
        }

        return $this;
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_order, $this->_state);
    }
}
