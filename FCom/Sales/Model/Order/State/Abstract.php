<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Sales_Model_Order_State_Abstract extends BClass
{
    protected $_context;

    protected $_type;

    protected $_value;

    protected $_options = [];

    protected $_orderStateField;

    static protected $_values = [];

    protected $_defaultValue;

    protected $_defaultValueClass;

    protected $_valueLabels = [];

    public function __construct($context, $type, $value, $options)
    {
        $this->_context = $context;
        $this->_type = $type;
        $this->_value = $value;
        $this->_options = $options;
        return $this;
    }

    public function changeState($value, $updateOrderField = true)
    {
        $this->_context->changeState($this->_type, $value, $updateOrderField);
        return $this;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function is($value)
    {
        return $this->_value === $value;
    }

    public function getValueLabel($value)
    {
        return !empty($this->_valueLabels[$value]) ? $this->_valueLabels[$value] : null;
    }

    public function __destruct()
    {
        unset($this->_order, $this->_options);
    }
}
