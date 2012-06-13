<?php

class FCom_ShippingUps extends BClass
{
    public static function bootstrap()
    {
        include_once __DIR__ . 'lib/UpsRate.php';
        FCom_Checkout_Api::i()->addShippingMethod('ShippingUps', 'FCom_ShippingUps');
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