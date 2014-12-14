<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_StateCustom_ConcreteAbstract
 *
 * @property FCom_Sales_Model_StateCustom $FCom_Sales_Model_StateCustom
 */
abstract class FCom_Sales_Model_StateCustom_ConcreteAbstract extends FCom_Core_Model_Abstract_State_Concrete
{
    protected static $_entityType;
    
    public function getAllValueLabels()
    {
        if (!$this->_valueLabels) {
            $this->_valueLabels = $this->FCom_Sales_Model_StateCustom->optionsByType(static::$_entityType);
        }
        return $this->_valueLabels;
    }

    public function setDefault()
    {
        $defaultState = $this->BConfig->get('modules/FCom_Sales/default_custom_state_' . static::$_entityType);
        return $this->changeState($defaultState);
    }
}
