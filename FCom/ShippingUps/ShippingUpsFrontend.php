<?php

class FCom_ShippingUps_Frontend extends BClass
{
    public static function bootstrap()
    {
        include_once __DIR__ .'/lib/UpsRate.php';
        FCom_Checkout_Model_Cart::i()->addShippingMethod('ShippingUps', 'FCom_ShippingUps_Ups');
    }
}
