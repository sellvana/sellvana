<?php

class Sellvana_Sales_Model_Order_Refund_Item extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_refund_item';
    protected static $_origClass = __CLASS__;

    public function getOrderItemsQtys()
    {
        $rItems = $this->orm('ri')
            ->left_outer_join('Sellvana_Sales_Model_Order_Refund', ['r.id', '=', 'ri.refund_id'], 'r')
            ->select('ri.*')
            ->select('r.state_overall')
            ->find_many();

        $result = [];
        foreach ($rItems as $rItem) {
            $oiId = $rItem->get('order_item_id');
            $qty = $rItem->get('qty');
            if (empty($result[$oiId]['qty_in_refunds'])) {
                $result[$oiId]['qty_in_refunds'] = $qty;
            } else {
                $result[$oiId]['qty_in_refunds'] += $qty;
            }
            if (in_array($rItem->get('state_overall'), [
                Sellvana_Sales_Model_Order_Refund_State_Overall::REFUNDED,
            ])) {
                if (empty($result[$oiId]['qty_refunded'])) {
                    $result[$oiId]['qty_refunded'] = $qty;
                } else {
                    $result[$oiId]['qty_refunded'] += $qty;
                }
            }
        }
        return $result;
    }
}