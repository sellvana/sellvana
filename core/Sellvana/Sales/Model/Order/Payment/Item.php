<?php

class Sellvana_Sales_Model_Order_Payment_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_payment_item';
    protected static $_origClass = __CLASS__;

    protected $_parentClass = 'Sellvana_Sales_Model_Order_Payment';
    protected $_parentField = 'payment_id';
    protected $_allField = 'qty_in_payments';
    protected $_doneField = 'qty_paid';
    protected $_doneStates = [
        Sellvana_Sales_Model_Order_Payment_State_Overall::PAID,
    ];

    public function getOrderItemsQtys(array $items = null)
    {
        return $this->_getOrderItemsQtys($items);
    }
}