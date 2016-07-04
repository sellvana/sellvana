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

    /**
     * @var Sellvana_Sales_Model_Order_Refund_State
     */
    protected $_state;

    /**
     * @var Sellvana_Sales_Model_Order_Return_Item[]
     */
    protected $_items;

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

    /**
     * Return the return items
     *
     * @param boolean $assoc
     * @return Sellvana_Sales_Model_Order_Item[]
     */
    public function items($assoc = true)
    {
        if (!$this->_items) {
            $this->_items = $this->Sellvana_Sales_Model_Order_Refund_Item->orm('ori')
                ->join('Sellvana_Sales_Model_Order_Item', ['oi.id', '=', 'ori.order_item_id'], 'oi')
                ->select('ori.*')->select(['oi.product_id', 'product_sku', 'inventory_id',
                    'inventory_sku', 'product_name'])
                ->where('refund_id', $this->id())->find_many_assoc();
        }
        return $assoc ? $this->_items : array_values($this->_items);
    }

    public function importFromOrder(Sellvana_Sales_Model_Order $order, array $amounts = [])
    {
        $this->order($order);
        $this->state()->overall()->setDefaultState();
        $this->state()->custom()->setDefaultState();
        $this->save();

        $items = $order->items();
        if ($amounts === null) {
            $amounts = [];
            foreach ($items as $item) {
                $amounts[$item->id()] = true;
            }
        }

        foreach ($amounts as $itemId => $amount) {
            if (empty($items[$itemId])) {
                throw new BException($this->_('Invalid item id: %s', $itemId));
            }
            /** @var Sellvana_Sales_Model_Order_Item $item */
            $item = $items[$itemId];
            $amountCanRefund = $item->getAmountCanRefund();
            if ($amount === true) {
                $amount = $amountCanRefund;
            } elseif ($amount <= 0 || $amount > $amountCanRefund) {
                throw new BException($this->_('Invalid amount to refund for %s: %s', [$item->get('product_sku'), $amount]));
            }
            $this->Sellvana_Sales_Model_Order_Refund_Item->create([
                'order_id' => $order->id(),
                'refund_id' => $this->id(),
                'order_item_id' => $item->id(),
                'amount' => $amount,
            ])->save();
        }

        return $this;
    }

    public function importFromPayment(Sellvana_Sales_Model_Order_Payment $payment)
    {
        //$payment->items()
    }
    
    public function register($done = false)
    {
        $order = $this->order();
        $orderItems = $order->items();
        $refundItems = $this->items();

        foreach ($refundItems as $cItem) {
            $oItem = $orderItems[$cItem->get('order_item_id')];
            $oItem->add($done ? 'amount_refunded' : 'amount_in_refunds', $cItem->get('amount'));
        }

        return $this;
    }

    public function unregister($done = false)
    {
        $order = $this->order();
        $orderItems = $order->items();
        $refundItems = $this->items();

        foreach ($refundItems as $cItem) {
            $oItem = $orderItems[$cItem->get('order_item_id')];
            $oItem->add($done ? 'amount_refunded' : 'amount_in_refunds', -$cItem->get('amount'));
        }

        return $this;
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_order, $this->_state);
    }
}
