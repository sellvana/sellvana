<?php

class FCom_PlainShipping extends BClass
{
    public static function bootstrap()
    {
        FCom_Checkout_Api::i()->addShippingMethod('PlainShipping', 'FCom_PlainShipping');
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