<?php

class Sellvana_Sales_Model_Order_Shipment_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_shipment_item';
    protected static $_origClass = __CLASS__;

    public function getOrderItemsQtys(array $items = null)
    {
        return $this->_getOrderItemsQtys($items, 'Sellvana_Sales_Model_Order_Shipment',
            'shipment_id', 'qty_in_shipments', 'qty_shipped', [
                Sellvana_Sales_Model_Order_Shipment_State_Overall::SHIPPED,
            ]
        );
    }
}