<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @var id
 * @var entity_type
 * @var state_code
 * @var state_label
 * @var
 */
class FCom_Sales_Model_StateCustom extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_sales_state_custom';
    protected static $_origClass = __CLASS__;

    protected static $_optionsByType;

    public function optionsByType($type = null)
    {
        if (empty(static::$_optionsByType)) {
            $states = $this->orm()->order_by_asc('state_label')->find_many();
            foreach ($states as $state) {
                static::$_optionsByType[$state->entity_type][$state->state_code] = $state->state_label;
            }
        }
        if (null === $type) {
            return static::$_optionsByType;
        } else {
            return !empty(static::$_optionsByType[$type]) ? static::$_optionsByType[$type] : [];
        }
    }
}
