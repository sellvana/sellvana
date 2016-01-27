<?php

/**
 * Class Sellvana_Sales_Model_Order_Cancel
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order_Item $Sellvana_Sales_Model_Order_Item
 * @property Sellvana_Sales_Model_Order_Cancel_State $Sellvana_Sales_Model_Order_Cancel_State
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 */

class Sellvana_Sales_Model_Order_Cancel extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_cancel';
    protected static $_origClass = __CLASS__;

    protected $_state;

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

    public function cancelOrderItems(Sellvana_Sales_Model_Order $order, $itemsData)
    {
        if (!preg_match_all('#^\s*([^\s]+)(\s*:\s*([^\s]+))?\s*$#', $itemsData, $matches, PREG_SET_ORDER)) {
            return $this;
        }
        $qtys = [];
        foreach ($matches as $m) {
            $qtys[$m[1]] = !empty($m[3]) ? $m[3] : true;
        }
        $skus = array_keys($qtys);
        $items = $order->items();
        foreach ($items as $i => $item) {
            if (!in_array($item->get('product_sku'), $skus)) {
                unset($items[$i]);
            }
        }
        if (!$items) {
            return [
                'error' => ['message' => 'No valid SKUs found'],
            ];
        }

        foreach ($items as $item) {
            $sku = $item->get('product_sku');
            $qty = $qtys[$sku] === true ? $item->getQtyCanCancel() : $qtys[$sku];
            $item->set('qty_to_cancel', $qty);
        }

        $result = [];
        $this->Sellvana_Sales_Main->workflowAction('adminCancelsOrderItems', [
            'order' => $order,
            'items' => $items,
            'result' => &$result,
        ]);

        return $this;
    }

    public function cancelItem($item)
    {

    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_order, $this->_state);
    }
}
