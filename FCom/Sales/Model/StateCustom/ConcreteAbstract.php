<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract

/**
 * Class FCom_Sales_Model_StateCustom_ConcreteAbstract
 *
 * @property FCom_Sales_Model_StateCustom $FCom_Sales_Model_StateCustom
 */ class FCom_Sales_Model_StateCustom_ConcreteAbstract extends FCom_Core_Model_Abstract_State_Concrete
{
    protected static $_entityType;

    protected function _initialize()
    {
        parent::_initialize();

        $this->_valueLabels = $this->FCom_Sales_Model_StateCustom->optionsByType(static::$_entityType);
    }

    public function setDefault()
    {
        $defaultState = $this->BConfig->get('modules/FCom_Sales/default_custom_state_' . static::$_entityType);
        return $this->changeState($defaultState);
    }
}
