<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Model_StateCustom_ConcreteAbstract
 *
 * @property Sellvana_Sales_Model_StateCustom $Sellvana_Sales_Model_StateCustom
 */
abstract class Sellvana_Sales_Model_StateCustom_ConcreteAbstract extends FCom_Core_Model_Abstract_State_Concrete
{
    protected static $_entityType;

    public function getAllValueLabels()
    {
        if (!$this->_valueLabels) {
            $this->_valueLabels = $this->Sellvana_Sales_Model_StateCustom->optionsByType(static::$_entityType);
        }
        return $this->_valueLabels;
    }

    public function setDefault()
    {
        $defaultState = $this->BConfig->get('modules/Sellvana_Sales/default_custom_state_' . static::$_entityType);
        return $this->changeState($defaultState);
    }
}
