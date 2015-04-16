<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_History extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_history';
    protected static $_origClass = __CLASS__;

    protected static $_fieldOptions = [
        'entity_type' => [
            'order' => 'Order',
            'order_item' => 'Order Item',
            'payment' => 'Payment',
            'shipment' => 'Shipment',
            'return' => 'Return',
            'refund' => 'Refund',
        ],
    ];
    
    public function __destruct()
    {
        parent::__destruct();
        unset($this->_order);
    }
}
