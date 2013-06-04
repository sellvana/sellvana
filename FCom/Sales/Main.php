<?php

class FCom_Sales_Main extends BClass
{
    protected $_registry = array();
    protected $_heap = array();

    public function addPaymentMethod($name, $class=null)
    {
        if (is_null($class)) $class = $name;
        $this->_registry['payment_method'][$name] = $class;
        return $this;
    }

    public function addCheckoutMethod($name, $class=null)
    {
        if (is_null($class)) $class = $name;
        $this->_registry['checkout_method'][$name] = $class;
        return $this;
    }

    public function addShippingMethod($name, $class=null)
    {
        if (is_null($class)) $class = $name;
        $this->_registry['shipping_method'][$name] = $class;
        return $this;
    }

    public function addDiscountMethod($name, $class=null)
    {
        if (is_null($class)) $class = $name;
        $this->_registry['discount_method'][$name] = $class;
        return $this;
    }

    public function addOrderTotalRow($name, $class=null)
    {
        if (is_null($class)) $class = $name;
        $this->_registry['order_total_row'][$name] = $class;
        return $this;
    }

    public function getShippingMethodClassName($name)
    {
        return !empty($this->_registry['shipping_method'][$name]) ? $this->_registry['shipping_method'][$name] : null;
    }

    protected function _getHeap($type, $name=null)
    {
        if (empty($this->_heap[$type])) {
            foreach ($this->_registry[$type] as $name=>$class) {
                $this->_heap[$type][$name] = $class::i();
            }
        }
        return is_null($name) ? $this->_heap[$type] :
            (!empty($this->_heap[$type][$name]) ? $this->_heap[$type][$name] : null);
    }

    public function getPaymentMethods()
    {
        return $this->_getHeap('payment_method');
    }

    public function getCheckoutMethods()
    {
        return $this->_getHeap('checkout_method');
    }

    public function getShippingMethods()
    {
        return $this->_getHeap('shipping_method');
    }

    public function getDiscountMethods()
    {
        return $this->_getHeap('discount_method');
    }

    public function getOrderTotalRows()
    {
        return $this->_getHeap('order_total_row');
    }
}

