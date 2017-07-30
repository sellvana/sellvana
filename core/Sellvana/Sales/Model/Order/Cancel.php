<?php

/**
 * Class Sellvana_Sales_Model_Order_Cancel
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order_Item $Sellvana_Sales_Model_Order_Item
 * @property Sellvana_Sales_Model_Order_Cancel_State $Sellvana_Sales_Model_Order_Cancel_State
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 * @property Sellvana_Sales_Model_Order_Cancel_Item $Sellvana_Sales_Model_Order_Cancel_Item
 */

class Sellvana_Sales_Model_Order_Cancel extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_cancel';
    protected static $_origClass = __CLASS__;

    protected $_state;

    /**
     * @var Sellvana_Sales_Model_Order_Cancel_Item[]
     */
    protected $_items;

    /**
     * @return Sellvana_Sales_Model_Order_Cancel_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->Sellvana_Sales_Model_Order_Cancel_State->factory($this);
        }
        return $this->_state;
    }

    /**
     * Return the cancel items
     *
     * @param boolean $assoc
     * @return Sellvana_Sales_Model_Order_Cancel_Item[]
     */
    public function items($assoc = true)
    {
        if (!$this->_items) {
            $this->_items = $this->Sellvana_Sales_Model_Order_Cancel_Item->orm('oci')
                ->join('Sellvana_Sales_Model_Order_Item', ['oi.id', '=', 'oci.order_item_id'], 'oi')
                ->select('oci.*')->select(['oi.product_id', 'product_sku', 'inventory_id',
                    'inventory_sku', 'product_name'])
                ->where('cancel_id', $this->id())->find_many_assoc();
        }
        return $assoc ? $this->_items : array_values($this->_items);
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
                throw new BException($this->_((('Invalid item id: %s')), $itemId));
            }
            /** @var Sellvana_Sales_Model_Order_Item $item */
            $item = $items[$itemId];
            $qtyCanCancel = $item->getQtyCanCancel();
            if ($qty === true) {
                $qty = $qtyCanCancel;
            } elseif ($qty <= 0 || $qty > $qtyCanCancel) {
                throw new BException($this->_((('Invalid quantity to cancel for %s: %s')), [$item->get('product_sku'), $qty]));
            }
            $this->Sellvana_Sales_Model_Order_Cancel_Item->create([
                'order_id' => $order->id(),
                'cancel_id' => $this->id(),
                'order_item_id' => $item->id(),
                'qty' => $qty,
            ])->save();
        }

        return $this;
    }

    public function register($done = false)
    {
        $order = $this->order();
        $orderItems = $order->items();
        $cancelItems = $this->items();

        foreach ($cancelItems as $cItem) {
            $oItem = $orderItems[$cItem->get('order_item_id')];
            $oItem->add($done ? 'qty_canceled' : 'qty_in_cancels', $cItem->get('qty'));
        }

        return $this;
    }

    public function unregister($done = false)
    {
        $order = $this->order();
        $orderItems = $order->items();
        $cancelItems = $this->items();

        foreach ($cancelItems as $cItem) {
            $oItem = $orderItems[$cItem->get('order_item_id')];
            $oItem->add($done ? 'qty_canceled' : 'qty_in_cancels', -$cItem->get('qty'));
        }

        return $this;
    }

    public function onBeforeSave()
    {
        parent::onBeforeSave();

        if (!$this->get('unique_id')) {
            $this->FCom_Core_Model_Seq->setNextChildId($this, 'Sellvana_Sales_Model_Order','order_id', 'SO', 'CL');
        }

        return $this;
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_order, $this->_state);
    }
}
