<?php

abstract class FCom_Core_Model_Abstract_State_Concrete extends BClass
{
    /**
     * @var FCom_Core_Model_Abstract_State_Context
     */
    protected $_context;

    /**
     * @var string
     */
    protected $_type;

    /**
     * @var string
     */
    protected $_value;

    /**
     * @var array|null
     */
    protected $_options = [];

    /**
     * @var string
     */
    protected $_defaultValue;

    /**
     * @var string
     */
    protected $_defaultValueClass;

    /**
     * @var array
     */
    protected $_valueLabels;

    /**
     * @var array
     */
    protected $_setValueNotificationTemplates = [];

    /**
     * @var array
     */
    protected $_unsetValueNotificationTemplates = [];

    /**
     * @var array
     */
    protected $_defaultMethods = [];

    /**
     * FCom_Core_Model_Abstract_State_Concrete constructor.
     *
     * @param null $context
     * @param null $type
     * @param null $value
     * @param null $options
     */
    public function __construct($context = null, $type = null, $value = null, $options = null)
    {
        $this->_context = $context;
        $this->_type    = $type;
        $this->_value   = $value;
        $this->_options = $options;

        $this->_initialize();

        return $this;
    }

    protected function _initialize()
    {
        // placeholder mainly for custom states
    }

    /**
     * Change current state to a different value and return a new state object
     *
     * @param string $value
     * @param bool $updateModelField
     *
     * @return static New state object
     * @throws BException
     * @todo Implement required and not required state contexts
     */
    public function changeState($value, $updateModelField = true)
    {
        if ($value === $this->getValue()) {
            return $this;
        }

        $valueLabels = $this->getAllValueLabels();
        if ($value && empty($valueLabels[$value])) {
            throw new BException("Invalid state value '" . $value . "' for type '" . $this->_type . "'");
        }

        $this->sendNotification(true); // Send onUnset notification (going out of state)
        $newState = $this->_context->changeState($this->_type, $value, $updateModelField);
        $newState->sendNotification(); // Send onSet notification (going into state)

        $class = $this->origClass() ?: get_class($this);
        $this->BEvents->fire($class . '::changeState', ['old_state' => $this, 'new_state' => $newState]);

        return $newState;
    }

    public function sendNotification($onUnset = false, $value = null)
    {
        $pool = $onUnset ? $this->_unsetValueNotificationTemplates : $this->_setValueNotificationTemplates;
        if (null === $value) {
            $value = $this->_value;
        }
        if (!empty($pool[$value])) {
            foreach ((array)$pool[$value] as $emailViewName) {
                $view = $this->BLayout->getView($emailViewName);
                if (!$view instanceof BViewEmpty) {
                    $view->set(['context' => $this->_context, 'type' => $this->_type, 'options' => $this->_options])
                        ->email();
                }
            }
        }

        return $this;
    }

    /**
     * @return FCom_Core_Model_Abstract_State_Context|null
     */
    public function getContext()
    {
        return $this->_context;
    }

    /**
     * @return FCom_Core_Model_Abstract|null
     */
    public function getModel()
    {
        return $this->_context->getModel();
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->_context->getState($this->_type);
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->_defaultValue;
    }

    /**
     * @return $this
     * @throws BException
     */
    public function setDefaultState()
    {
        $this->changeState($this->getDefaultValue());

        return $this;
    }

    /**
     * @return null|string
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @return null|string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param $value
     *
     * @return bool
     */
    public function is($value)
    {
        return $this->_value === $value;
    }

    /**
     * @param null $value
     *
     * @return string|null
     */
    public function getValueLabel($value = null)
    {
        if (null === $value) {
            $value = $this->getValue();
        }
        $valueLabels = $this->getAllValueLabels();

        return !empty($valueLabels[$value]) ? $valueLabels[$value] : null;
    }

    /**
     * @return array
     */
    public function getAllValueLabels()
    {
        return $this->_valueLabels;
    }

    public function __destruct()
    {
        unset($this->_model, $this->_options);
    }
}
