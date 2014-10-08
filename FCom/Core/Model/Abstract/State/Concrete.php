<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Core_Model_Abstract_State_Concrete extends BClass
{
    protected $_context;

    protected $_type;

    protected $_value;

    protected $_options = [];

    protected $_defaultValue;

    protected $_defaultValueClass;

    protected $_valueLabels = [];

    protected $_setValueNotificationTemplates = [];

    protected $_unsetValueNotificationTemplates = [];

    public function __construct($context, $type, $value, $options)
    {
        $this->_context = $context;
        $this->_type = $type;
        $this->_value = $value;
        $this->_options = $options;

        return $this;
    }

    public function changeState($value, $updateModelField = true)
    {
        $this->sendNotification(true); // Send onUnset notification (going out of state)
        $newState = $this->_context->changeState($this->_type, $value, $updateModelField);
        $newState->sendNotification(); // Send onSet notification (going into state)

        return $newState;
    }

    public function sendNotification($onUnset = false, $value = null)
    {
        $pool = $unUnset ? $this->_unsetValueNotificationTemplates : $this->_setValueNotificationTemplates;
        if (null === $value) {
            $value = $this->_value;
        }
        if (!empty($pool[$value])) {
            $this->BLayout->view($pool[$value])
                ->set(['context' => $this->_context, 'type' => $this->_type, 'options' => $this->_options])
                ->email();
        }
        return $this;
    }

    public function getState()
    {
        return $this->_context->getState($this->_type);
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
        unset($this->_model, $this->_options);
    }
}
