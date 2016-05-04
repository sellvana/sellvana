<?php

class Sellvana_Sales_Model_Order_Cancel_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_cancel_item';
    protected static $_origClass = __CLASS__;

    public function getOrderItemsQtys(array $items = null)
    {
        return $this->_getOrderItemsQtys($items, 'Sellvana_Sales_Model_Order_Cancel',
            'cancel_id', 'qty_in_cancels', 'qty_canceled', [
                Sellvana_Sales_Model_Order_Cancel_State_Overall::PENDING,
                Sellvana_Sales_Model_Order_Cancel_State_Overall::APPROVED,
                Sellvana_Sales_Model_Order_Cancel_State_Overall::COMPLETE,
            ]
        );
    }
}