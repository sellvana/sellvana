<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_SalesTax_Model_RuleProductClass extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_salestax_rule_product_class';
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['rule_id', 'product_class_id'],
        'related'    => [
            'rule_id'          => 'Sellvana_SalesTax_Model_Rule.id',
            'product_class_id' => 'Sellvana_SalesTax_Model_ProductClass.id'
        ],
    ];
}
