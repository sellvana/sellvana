<?php

class FCom_ShippingPlain extends BClass
{
    public static function bootstrap()
    {
        FCom_Checkout_Model_Cart::i()->addShippingMethod('ShippingPlain', 'FCom_ShippingPlain');
    }

    public function getEstimate()
    {
        return '2-4 days';
    }

    public function getPrice()
    {
        return rand(10,100);
    }

    public function getDescription()
    {
        return 'Free standard Shipping';
    }
}