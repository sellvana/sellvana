<?php

class FCom_Checkout_Api extends BClass
{
    protected $shippingMethods = array();
    protected $shippingClasses = array();

    public static function bootstrap() {}

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_Checkout
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    public function addShippingMethod($method, $class)
    {
        $this->shippingMethods[$method] = $class;
    }

    /**
     *
     * @return Array of Shipping Method objects
     */
    public function getShippingMethods()
    {
        if (!$this->shippingMethods) {
            return false;
        }
        if (empty($this->shippingClasses)) {
            foreach($this->shippingMethods as $method => $class) {
                $this->shippingClasses[$method] = $class::i();
            }
        }
        return $this->shippingClasses;
    }

    public function getShippingMethod($method)
    {
        $this->getShippingMethods();
        if (!empty($this->shippingClasses[$method])){
            return $this->shippingClasses[$method];
        } else {
            return false;
        }
    }
}

