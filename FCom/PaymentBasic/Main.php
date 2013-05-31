<?php

class FCom_PaymentBasic_Main extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Frontend/views');

        FCom_Sales_Main::i()->addPaymentMethod('basic', 'FCom_PaymentBasic_PaymentMethod');
    }

    public function getName()
    {
        return 'Check / Money Order';
    }

    public function processPayment()
    {
        die("Check / Money Order payment not imlemented yet");
    }
}
