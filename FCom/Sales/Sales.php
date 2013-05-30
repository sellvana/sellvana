<?php

class FCom_Sales extends BClass
{
    protected $_registry = array();
    protected $_heap = array();

    public static function bootstrap()
    {

    }

    public function registerPaymentMethod($name, $class)
    {
        $this->_registry['payment_method'][$name] = $class;
        return $this;
    }

    public function registerCheckoutMethod($name, $class)
    {
        $this->_registry['checkout_method'][$name] = $class;
        return $this;
    }

    public function registerShippingMethod($name, $class)
    {
        $this->_registry['shipping_method'][$name] = $class;
        return $this;
    }

    public function registerDiscountMethod($name, $class)
    {
        $this->_registry['discount_method'][$name] = $class;
        return $this;
    }

    public function registerOrderTotalRow($name, $class)
    {
        $this->_registry['order_total_row'][$name] = $class;
        return $this;
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

