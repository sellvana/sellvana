<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Core_Model_Abstract_State_Context extends BClass
{
    /**
     * Model linked
     *
     * @var FCom_Core_Model_Abstract
     */
    protected $_model;

    /**
     * Concrete state objects for this model
     *
     * @var array
     */
    protected $_stateObjects = [];

    /**
     * Default classes for each type of model state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [];

    /**
     * Library of available state values by type
     *
     * @var array
     */
    static protected $_stateValues = [];

    public function __construct($model)
    {
        $this->_model = $model;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function addStateValue($type, $value, $options = [])
    {
        if (empty($options['class'])) {
            $options['class'] = static::$_defaultStateClasses[$type];
        }
        static::$_stateValues[$type][$value] = $options;
        return $this;
    }

    protected function _getStateObject($type, $value = null)
    {
        if (null === $value) {
            $value = $this->_getModelStateValue($type);
            if (!empty($this->_stateObjects[$type]) && $this->_stateObjects[$type]->is($value)) {
                return $this->_stateObjects[$type];
            }
        }
        if (!empty(static::$_stateValues[$type][$value])) {
            $options = static::$_stateValues[$type][$value];
            $class = $options['class'];
        } elseif (!empty(static::$_defaultStateClasses[$type])) {
            $class = static::$_defaultStateClasses[$type];
            $options = ['class' => $class];
        } else {
            throw new BException('Invalid state type: ' . $type);
        }
        if (!$class instanceof FCom_Sales_Model_State_Abstract) {
            throw new BException('Invalid state class: ' . $class);
        }

        $this->_stateObjects[$type] = $this->BClassRegistry->instance($class, true, [$this, $type, $value, $options]);
        return $this->_stateObjects[$type];
    }

    protected function _getModelStateField($type)
    {
        return 'state_' . $type;
    }

    protected function _getModelStateValue($type)
    {
        return $this->_model->get($this->_getModelStateField($type));
    }

    public function getState($type)
    {
        $stateField = $this->_getModelStateField($type);
        return $this->_model->get($stateField);
    }

    public function changeState($type, $value, $updateModelField = true)
    {
        $stateField = $this->_getModelStateField($type);
        $oldValue = $this->_model->get($stateField);
        $this->_stateObjects[$type] = $this->_getStateObject($type, $value);
        if ($updateModelField) {
            $this->_model->set($stateField, $value);
        }

        return $this->_stateObjects[$type];
    }

    public function __destruct()
    {
        unset($this->_model, $this->_stateObjects, $this->_stateValues);
    }
}
