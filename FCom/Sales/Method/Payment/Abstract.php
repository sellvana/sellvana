<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Sales_Method_Payment_Abstract extends BClass implements
    FCom_Sales_Method_Payment_Interface
{
    protected $_cart;
    protected $_order;
    protected $_orderOptions;
    protected $_details;
    protected $_sortOrder = 50;
    protected $_name;

    protected $_capabilities = [
        'pay'           => 1,
        'refund'        => 1,
        'void'          => 1,
        'recurring'     => 0,
        'pay_partial'   => 0,
        'pay_online'    => 0,
        'refund_online' => 0,
        'void_online'   => 0,
    ];

    public function can($capability)
    {
        if (isset($this->_capabilities[strtolower($capability)])) {
            return (bool) $this->_capabilities[strtolower($capability)];
        }
        return false;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /**
     * Assign sales entity
     *
     * Prepare to pay for sales object here
     *
     * @param $order
     * @param $options
     * @return $this
     */
    public function setSalesOrder($order, $options = null)
    {
        $this->_order = $order;
        $this->_orderOptions = $options;

        return $this;
    }

    /**
     * Set any details gathered during checkout process
     * @param array $details
     * @return $this
     */
    public function setDetails($details)
    {
        $this->_details = $details;
        return $this;
    }

    /**
     * Get public data
     *
     * Get data which can be saved, should not include any sensitive data such as credit card numbers, personal ids, etc.
     * @return array
     */
    public function getPublicData()
    {
        return $this->_details;
    }

    /**
     * @param $cart
     * @return $this
     * @internal This replaces initCart in basic payment
     */
    public function setCartEntity($cart)
    {
        $this->_cart = $cart;
        return $this;
    }

    public function asArray()
    {
        return ["name" => $this->getName()];
    }

    public function set($name, $value)
    {
        return $this->_details[$name] = $value;
    }

    public function get($name, $default = null)
    {
        return isset($this->_details[$name]) ? $this->_details[$name] : $default;
    }
}
