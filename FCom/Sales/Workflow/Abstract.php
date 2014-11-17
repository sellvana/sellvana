<?php

abstract

/**
 * Class FCom_Sales_Workflow_Abstract
 *
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 */ class FCom_Sales_Workflow_Abstract extends BClass
{
    protected $_flagRegistered = false;
    protected $_localHooks = [];

    public function registerWorkflow()
    {
        if ($this->_flagRegistered) {
            return $this;
        }
        $this->_flagRegistered = true;

        if ($this->_localHooks) {
            $class = $this->origClass();
            $events = $this->BEvents;
            foreach ($this->_localHooks as $key => $method) {
                $hook = !is_numeric($key) ? $key : $method;
                $events->on('FCom_Sales_Workflow::' . $hook, [$this, $method]);
            }
        }

        return $this;
    }

    /**
     * @param $args
     * @return FCom_Customer_Model_Customer
     */
    protected function _getCustomer($args)
    {
        if (!empty($args['customer'])) {
            $customer = $args['customer'];
        } else {
            $customer = $this->FCom_Customer_Model_Customer->sessionUser();
        }
        return $customer;
    }

    /**
     * @param $args
     * @param bool $createIfNeeded
     * @return FCom_Sales_Model_Cart
     */
    protected function _getCart($args, $createIfNeeded = false)
    {
        if (!empty($args['cart'])) {
            $cart = $args['cart'];
        } else {
            $cart = $this->FCom_Sales_Model_Cart->sessionCart($createIfNeeded);
        }
        return $cart;
    }
}
