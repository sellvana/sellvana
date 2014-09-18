<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Sales_Model_Order_State extends BClass
{
    /**
     * Order linked
     *
     * @var FCom_Sales_Model_Order
     */
    protected $_order;

    /**
     * Concrete state objects for this order
     *
     * @var array
     */
    protected $_stateObjects = [];

    /**
     * Default classes for each type of order state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        'overall' => 'FCom_Sales_Model_Order_State_Overall',
        'delivery' => 'FCom_Sales_Model_Order_State_Delivery',
        'payment' => 'FCom_Sales_Model_Order_State_Payment',
        'custom' => 'FCom_Sales_Model_Order_State_Custom',
    ];

    /**
     * Library of available state values by type
     *
     * @var array
     */
    static protected $_stateValues = [];

    public function __construct($order)
    {
        $this->_order = $order;
    }

    public function getOrder()
    {
        return $this->_order;
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
            $value = $this->_getOrderStateValue($type);
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
            throw new BException('Invalid order state type: ' . $type);
        }
        if (!$class instanceof FCom_Sales_Model_Order_State_Abstract) {
            throw new BException('Invalid order state class: ' . $class);
        }

        $this->_stateObjects[$type] = $this->BClassRegistry->instance($class, true, [$this, $type, $value, $options]);
        return $this->_stateObjects[$type];
    }

    protected function _getOrderStateField($type)
    {
        return 'state_' . $type;
    }

    protected function _getOrderStateValue($type)
    {
        return $this->_order->get($this->_getOrderStateField($type));
    }

    public function changeState($type, $value, $updateOrderField = true)
    {
        $stateField = $this->_getOrderStateField($type)
        $oldValue = $this->_order->get($stateField);
        $this->_stateObjects[$type] = $this->_getStateObject($type, $value);
        if ($updateOrderField) {
            $this->_order->set($stateField, $value);
        }

        return $this;
    }

    public function overall()
    {
        return $this->_getStateObject('overall');
    }

    public function delivery()
    {
        return $this->_getStateObject('delivery');
    }

    public function payment()
    {
        return $this->_getStateObject('payment');
    }

    public function custom()
    {
        return $this->_getStateObject('custom');
    }

    public function __destruct()
    {
        unset($this->_order, $this->_stateObjects, $this->_stateValues);
    }
}
