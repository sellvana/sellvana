<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Rewards_Model_Rule
 *
 */
class Sellvana_Rewards_Model_Rule extends FCom_Core_Model_Abstract
{
    protected static $_table        = 'fcom_rewards_rule';
    protected static $_origClass    = __CLASS__;
    protected static $_fieldOptions = [
        'conditions_operator' => [
            "always" => "Apply Always",
            "all"    => "All Conditions Have to Match",
            "any"    => "Any Condition Has to Match",
        ],
        'conditions_options'  => [
            "sku"         => "Products",
            "category"    => "Categories",
            "combination" => "Attribute Combination",
            "total"       => "Cart Total",
            "shipping"    => "Shipping Destination",
        ]
    ];
}
