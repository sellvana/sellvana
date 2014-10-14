<?php

abstract class FCom_Sales_Workflow_Abstract extends BClass
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
            foreach ($this->_localHooks as $hook) {
                $events->on($class . '::' . $hook, [$this, $hook]);
            }
        }

        return $this;
    }

    protected function _getCart($args, $createIfNeeded = false)
    {
        if (!empty($args['cart'])) {
            $cart = $args['cart'];
        } else {
            $cart = $this->FCom_Sales_Model_Cart->sessionCart($createIfNeeded);
        }
        return $cart;
    }

    protected function _getCustomer($args)
    {
        if (!empty($args['customer'])) {
            $customer = $args['customer'];
        } else {
            $customer = $this->FCom_Customer_Model_Customer->sessionUser();
        }
        return $customer;
    }
}
