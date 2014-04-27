<?php

class FCom_ShippingUps_Main extends BClass
{
    public static function bootstrap()
    {
        FCom_Sales_Main::i()->addShippingMethod( 'ups', 'FCom_ShippingUps_ShippingMethod' );
    }
}
