<?php

/**
 * Class Sellvana_Sales_Model_Order_Payment_Item
 */
class Sellvana_Sales_Model_Order_Payment_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_payment_item';
    protected static $_origClass = __CLASS__;

    protected $_parentClass = 'Sellvana_Sales_Model_Order_Payment';
    protected $_parentField = 'payment_id';
    protected $_allField = 'amount_in_payments';
    protected $_doneField = '';
    protected $_sumField = 'amount';

}