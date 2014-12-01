<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Core_Model_Abstract_State_Concrete extends BClass
{
    protected $_context;

    protected $_type;

    protected $_value;

    protected $_options = [];

    protected $_defaultValue;

    protected $_defaultValueClass;

    protected $_valueLabels;

    protected $_setValueNotificationTemplates = [];

    protected $_unsetValueNotificationTemplates = [];

    public function __construct($context, $type, $value, $options)
    {
        $this->_context = $context;
        $this->_type = $type;
        $this->_value = $value;
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
     * @return static New state object
     * @throws BException
     * @todo Implement required and not required state contexts
     */
    public function changeState($value, $updateModelField = true)
    {
        if ($value && empty($this->_valueLabels[$value])) {
            throw new BException("Invalid state value '" . $value . "' for type '" . $this->_type . "'");
        }

        $this->sendNotification(true); // Send onUnset notification (going out of state)
        $newState = $this->_context->changeState($this->_type, $value, $updateModelField);
        $newState->sendNotification(); // Send onSet notification (going into state)

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
                $this->BLayout->view($emailViewName)
                    ->set(['context' => $this->_context, 'type' => $this->_type, 'options' => $this->_options])
                    ->email();
            }
        }
        return $this;
    }

    public function getState()
    {
        return $this->_context->getState($this->_type);
    }

    public function getDefaultValue()
    {
        return $this->_defaultValue;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function is($value)
    {
        return $this->_value === $value;
    }

    public function getValueLabel($value = null)
    {
        if (null === $value) {
            $value = $this->getValue();
        }
        return !empty($this->_valueLabels[$value]) ? $this->_valueLabels[$value] : null;
    }

    public function __destruct()
    {
        unset($this->_model, $this->_options);
    }
}
