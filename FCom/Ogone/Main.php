<?php

class FCom_Ogone_Main extends BClass
{
    static public function bootstrap()
    {
        BApp::m('FCom_Ogone')->autoload('lib');

        FCom_Sales_Main::i()
            ->addPaymentMethod('ogone', 'FCom_Ogone_PaymentMethod')
            ->addCheckoutMethod('ogone', 'FCom_Ogone_CheckoutMethod')
        ;
    }
}