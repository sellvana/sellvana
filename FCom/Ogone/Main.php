<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Ogone_Main extends BClass
{
    static public function bootstrap()
    {
        FCom_Sales_Main::i()
            ->addPaymentMethod('ogone', 'FCom_Ogone_PaymentMethod')
            ->addCheckoutMethod('ogone', 'FCom_Ogone_CheckoutMethod')
        ;
    }
}