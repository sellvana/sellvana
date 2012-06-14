<?php

class FCom_ShippingPlain extends FCom_Checkout_Model_Shipping_Abstract
{
    public static function bootstrap()
    {
        FCom_Checkout_Model_Cart::i()->addShippingMethod('ShippingPlain', 'FCom_ShippingPlain');
    }

    public function getEstimate()
    {
        return 'approx. 2-4 days';
    }

    public function getServices()
    {
        return array('01' => 'Air', '02' => 'Ground');
    }

    public function getRateCallback($cart)
    {
        return rand(10,100);
    }

    public function getError()
    {
        return '';
    }

    public function getDescription()
    {
        return 'Free standard Shipping';
    }
}