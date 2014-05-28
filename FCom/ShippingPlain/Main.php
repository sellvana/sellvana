<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ShippingPlain_Main extends BClass
{
    public static function bootstrap()
    {
        FCom_Sales_Main::i()->addShippingMethod('plain', 'FCom_ShippingPlain_ShippingMethod');
    }
}