<?php

class Sellvana_Sales_Model_Order_Refund_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_refund_item';
    protected static $_origClass = __CLASS__;

    public function getOrderItemsQtys(array $items = null)
    {
        return $this->_getOrderItemsQtys($items, 'Sellvana_Sales_Model_Order_Refund',
            'refund_id', 'qty_in_refunds', 'qty_refunded', [
                Sellvana_Sales_Model_Order_Refund_State_Overall::REFUNDED,
            ]
        );
    }
}