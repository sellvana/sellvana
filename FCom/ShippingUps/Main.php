<?php

class FCom_ShippingUps_Main extends BClass
{
    public static function bootstrap()
    {
        include_once __DIR__ .'/lib/UpsRate.php';
        //FCom_Sales_Main::i()->addShippingMethod('ups', 'FCom_ShippingUps_Ups'); // TODO: update shipping method class
    }
}
