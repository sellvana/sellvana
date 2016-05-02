<?php

class Sellvana_Sales_Model_Order_Shipment_Item extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_shipment_item';
    protected static $_origClass = __CLASS__;

    public function getOrderItemsQtys()
    {
        $sItems = $this->orm('si')
            ->left_outer_join('Sellvana_Sales_Model_Order_Shipment', ['s.id', '=', 'si.shipment_id'], 's')
            ->select('si.*')
            ->select('s.state_overall')
            ->find_many();

        $result = [];
        foreach ($sItems as $sItem) {
            $oiId = $sItem->get('order_item_id');
            $qty = $sItem->get('qty');
            if (empty($result[$oiId]['qty_in_shipments'])) {
                $result[$oiId]['qty_in_shipments'] = $qty;
            } else {
                $result[$oiId]['qty_in_shipments'] += $qty;
            }
            if (in_array($sItem->get('state_overall'), [
                Sellvana_Sales_Model_Order_Shipment_State_Overall::SHIPPED,
            ])) {
                if (empty($result[$oiId]['qty_shipped'])) {
                    $result[$oiId]['qty_shipped'] = $qty;
                } else {
                    $result[$oiId]['qty_shipped'] += $qty;
                }
            }
        }
        return $result;
    }
}