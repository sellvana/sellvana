<?php

class Sellvana_Sales_Model_Order_Cancel_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_cancel_item';
    protected static $_origClass = __CLASS__;

    protected $_parentClass = 'Sellvana_Sales_Model_Order_Cancel';
    protected $_parentField = 'cancel_id';
    protected $_allField = 'qty_in_cancels';
    protected $_doneField = 'qty_canceled';
    protected $_doneStates = [
        Sellvana_Sales_Model_Order_Cancel_State_Overall::PENDING,
        Sellvana_Sales_Model_Order_Cancel_State_Overall::APPROVED,
        Sellvana_Sales_Model_Order_Cancel_State_Overall::COMPLETE,
    ];

    public function getOrderItemsQtys(array $items = null)
    {
        return $this->_getOrderItemsQtys($items);
    }
}