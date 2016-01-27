<?php

class Sellvana_SalesTax_Model_RuleCustomerClass extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_salestax_rule_customer_class';
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['rule_id', 'customer_class_id'],
        'related'    => [
            'rule_id'           => 'Sellvana_SalesTax_Model_Rule.id',
            'customer_class_id' => 'Sellvana_SalesTax_Model_CustomerClass.id'
        ],
    ];
}
