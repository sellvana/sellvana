<?php

class FCom_ShippingUps extends BClass
{
    public static function bootstrap()
    {
        FCom_Checkout_Model_Cart::i()->addShippingMethod('ShippingUps', 'FCom_ShippingUps');
    }

    public function getEstimate()
    {
        return '1-2 days';
    }

    public function getPrice()
    {
        return rand(100, 500);
    }

    public function getDescription()
    {
        return 'Universal post service';
    }
}