<?php

class Sellvana_Sales_Model_Order_Return_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_return_item';
    protected static $_origClass = __CLASS__;

    protected $_parentClass = 'Sellvana_Sales_Model_Order_Return';
    protected $_parentField = 'return_id';
    protected $_allField = 'qty_in_returns';
    protected $_doneField = 'qty_returned';
    protected $_doneStates = [
        Sellvana_Sales_Model_Order_Return_State_Overall::PENDING,
        Sellvana_Sales_Model_Order_Return_State_Overall::RMA_SENT,
        Sellvana_Sales_Model_Order_Return_State_Overall::RECEIVED,
        Sellvana_Sales_Model_Order_Return_State_Overall::APPROVED,
        Sellvana_Sales_Model_Order_Return_State_Overall::RESTOCKED,
    ];

    public function getOrderItemsQtys(array $items = null)
    {
        return $this->_getOrderItemsQtys($items);
    }
}