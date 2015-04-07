<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_SalesTax_Model_CustomerTax extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_salestax_customer_tax';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['customer_id', 'customer_class_id'],
        'related'    => [
            'customer_id'       => 'Sellvana_Customer_Model_Customer.id',
            'customer_class_id' => 'Sellvana_SalesTax_Model_CustomerClass.id'
        ],
    ];
}
