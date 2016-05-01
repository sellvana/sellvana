<?php

class Sellvana_Sales_Model_Order_Cancel_Item extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_cancel_item';
    protected static $_origClass = __CLASS__;

    public function getOrderItemsQtys()
    {
        $cItems = $this->orm('ci')
            ->left_outer_join('Sellvana_Sales_Model_Order_Cancel', ['c.id', '=', 'ci.cancel_id'], 'c')
            ->select('ci.*')
            ->select('c.state_overall')
            ->find_many();

        $result = [];
        foreach ($cItems as $cItem) {
            $oiId = $cItem->get('order_item_id');
            $qty = $cItem->get('qty');
            if (empty($result[$oiId]['qty_in_cancels'])) {
                $result[$oiId]['qty_in_cancels'] = $qty;
            } else {
                $result[$oiId]['qty_in_cancels'] += $qty;
            }
            if (in_array($cItem->get('state_overall'), [
                Sellvana_Sales_Model_Order_Cancel_State_Overall::PENDING,
                Sellvana_Sales_Model_Order_Cancel_State_Overall::APPROVED,
                Sellvana_Sales_Model_Order_Cancel_State_Overall::COMPLETE,
            ])) {
                if (empty($result[$oiId]['qty_canceled'])) {
                    $result[$oiId]['qty_canceled'] = $qty;
                } else {
                    $result[$oiId]['qty_canceled'] += $qty;
                }
            }
        }
        return $result;
    }
}