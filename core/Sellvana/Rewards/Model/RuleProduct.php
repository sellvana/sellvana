<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Rewards_Model_RuleProduct
 *
 */
class Sellvana_Rewards_Model_RuleProduct extends FCom_Core_Model_Abstract
{
    protected static $_table     = 'fcom_rewards_rule_product';
    protected static $_origClass = __CLASS__;

    public function fetchProductRules($pId)
    {
        return $this->orm('rp')->where('rp.product_id', $pId)
            ->join('Sellvana_Rewards_Model_Rule', ['r.id', '=', 'rp.rule_id'], 'r')
            ->find_many();
    }
}