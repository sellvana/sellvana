<?php

class Sellvana_Sales_Model_Order_Return_Item extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_return_item';
    protected static $_origClass = __CLASS__;

    public function getOrderItemsQtys()
    {
        $rItems = $this->orm('ri')
            ->left_outer_join('Sellvana_Sales_Model_Order_Return', ['r.id', '=', 'ri.return_id'], 'r')
            ->select('ri.*')
            ->select('r.state_overall')
            ->find_many();

        $result = [];
        foreach ($rItems as $rItem) {
            $oiId = $rItem->get('order_item_id');
            $qty = $rItem->get('qty');
            if (empty($result[$oiId]['qty_in_returns'])) {
                $result[$oiId]['qty_in_returns'] = $qty;
            } else {
                $result[$oiId]['qty_in_returns'] += $qty;
            }
            if (in_array($rItem->get('state_overall'), [
                Sellvana_Sales_Model_Order_Return_State_Overall::PENDING,
                Sellvana_Sales_Model_Order_Return_State_Overall::RMA_SENT,
                Sellvana_Sales_Model_Order_Return_State_Overall::RECEIVED,
                Sellvana_Sales_Model_Order_Return_State_Overall::APPROVED,
                Sellvana_Sales_Model_Order_Return_State_Overall::RESTOCKED,
            ])) {
                if (empty($result[$oiId]['qty_returned'])) {
                    $result[$oiId]['qty_returned'] = $qty;
                } else {
                    $result[$oiId]['qty_returned'] += $qty;
                }
            }
        }
        return $result;
    }
}