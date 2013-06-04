<?php

class FCom_PaymentCC_Frontend extends BClass
{
    static public function bootstrap()
    {
        FCom_Sales_Main::i()->addPaymentMethod('cc', 'FCom_PaymentCC_PaymentMethod');
    }
}
