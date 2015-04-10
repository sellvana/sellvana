<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_SalesTax_Model_RuleZone extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_salestax_rule_zone';
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['rule_id', 'zone_id'],
        'related'    => [
            'rule_id' => 'Sellvana_SalesTax_Model_Rule.id',
            'zone_id' => 'Sellvana_SalesTax_Model_Zone.id'
        ],
    ];
}
