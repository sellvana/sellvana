<?php

/**
 * Class Sellvana_Sales_Model_Order_Return
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 * @property Sellvana_Sales_Model_Order_Return_State $Sellvana_Sales_Model_Order_Return_State
 * @property Sellvana_Sales_Model_Order_Return_Item $Sellvana_Sales_Model_Order_Return_Item
 */

class Sellvana_Sales_Model_Order_Return extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_return';
    protected static $_origClass = __CLASS__;

    protected $_state;

    /**
     * @return Sellvana_Sales_Model_Order_Return_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->Sellvana_Sales_Model_Order_Return_State->factory($this);
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
            $qtyCanReturn = $item->getQtyCanReturn();
            if ($qty === true) {
                $qty = $qtyCanReturn;
            } elseif ($qty <= 0 || $qty > $qtyCanReturn) {
                throw new BException($this->_('Invalid quantity to return for %s: %s', [$item->get('product_sku'), $qty]));
            }
            $this->Sellvana_Sales_Model_Order_Return_Item->create([
                'order_id' => $order->id(),
                'return_id' => $this->id(),
                'order_item_id' => $item->id(),
                'qty' => $qty,
            ])->save();
        }

        $this->state()->overall()->setDefaultState();
        $this->state()->custom()->setDefaultState();
        return $this;
    }

    public function returnOrderItems(Sellvana_Sales_Model_Order $order, array $qtys)
    {
        $items = $order->items();
        foreach ($qtys as $itemId => $qty) {
            if (empty($items[$itemId])) {
                continue;
            }
            $item = $items[$itemId];
            if ($qty === true) {
                $qty = $item->getQtyCanReturn();
            }
            $item->set('qty_to_return', $qty);
        }

        $result = [];
        $this->Sellvana_Sales_Main->workflowAction('adminCancelsOrderItems', [
            'order' => $order,
            'items' => $items,
            'result' => &$result,
        ]);

        return $result;
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_order, $this->_state);
    }
}
