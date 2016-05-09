<?php

class Sellvana_Sales_Model_Order_Refund_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_refund_item';
    protected static $_origClass = __CLASS__;

    protected $_parentClass = 'Sellvana_Sales_Model_Order_Refund';
    protected $_parentField = 'refund_id';
    protected $_allField = 'qty_in_refunds';
    protected $_doneField = 'qty_refunded';
    protected $_doneStates = [
        Sellvana_Sales_Model_Order_Refund_State_Overall::REFUNDED,
    ];
    
}