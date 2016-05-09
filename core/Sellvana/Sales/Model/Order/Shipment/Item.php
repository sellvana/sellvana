<?php

class Sellvana_Sales_Model_Order_Shipment_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_shipment_item';
    protected static $_origClass = __CLASS__;

    protected $_parentClass = 'Sellvana_Sales_Model_Order_Shipment';
    protected $_parentField = 'shipment_id';
    protected $_allField = 'qty_in_shipments';
    protected $_doneField = 'qty_shipped';
    protected $_doneStates = [
        Sellvana_Sales_Model_Order_Shipment_State_Overall::SHIPPED,
    ];
}