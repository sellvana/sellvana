<?php

class Sellvana_Sales_Model_Order_Return_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_return_item';
    protected static $_origClass = __CLASS__;

    public function getOrderItemsQtys(array $items = null)
    {
        return $this->_getOrderItemsQtys($items, 'Sellvana_Sales_Model_Order_Return', 
            'return_id', 'qty_in_returns', 'qty_returned', [
                Sellvana_Sales_Model_Order_Return_State_Overall::PENDING,
                Sellvana_Sales_Model_Order_Return_State_Overall::RMA_SENT,
                Sellvana_Sales_Model_Order_Return_State_Overall::RECEIVED,
                Sellvana_Sales_Model_Order_Return_State_Overall::APPROVED,
                Sellvana_Sales_Model_Order_Return_State_Overall::RESTOCKED,
            ]
        );
    }
}