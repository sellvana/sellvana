<?php

class Sellvana_Sales_Model_Order_Payment_Item extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_payment_item';
    protected static $_origClass = __CLASS__;

    public function getOrderItemsQtys()
    {
        $pItems = $this->orm('pi')
            ->left_outer_join('Sellvana_Sales_Model_Order_Payment', ['p.id', '=', 'pi.payment_id'], 'p')
            ->select('pi.*')
            ->select('p.state_overall')
            ->find_many();
        
        $result = [];
        foreach ($pItems as $pItem) {
            $oiId = $pItem->get('order_item_id');
            $qty = $pItem->get('qty');
            if (empty($result[$oiId]['qty_in_payments'])) {
                $result[$oiId]['qty_in_payments'] = $qty;
            } else {
                $result[$oiId]['qty_in_payments'] += $qty;
            }
            if (in_array($pItem->get('state_overall'), [
                Sellvana_Sales_Model_Order_Payment_State_Overall::PAID,
            ])) {
                if (empty($result[$oiId]['qty_paid'])) {
                    $result[$oiId]['qty_paid'] = $qty;
                } else {
                    $result[$oiId]['qty_paid'] += $qty;
                }
            }
        }
        return $result;
    }
}