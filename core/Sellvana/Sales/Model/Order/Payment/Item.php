<?php

class Sellvana_Sales_Model_Order_Payment_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_payment_item';
    protected static $_origClass = __CLASS__;

    public function getOrderItemsQtys(array $items = null)
    {
        return $this->_getOrderItemsQtys($items, 'Sellvana_Sales_Model_Order_Payment',
            'payment_id', 'qty_in_payments', 'qty_paid', [
                Sellvana_Sales_Model_Order_Payment_State_Overall::PAID,
            ]
        );
    }
}