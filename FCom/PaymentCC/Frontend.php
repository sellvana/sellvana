<?php

class FCom_PaymentCC_Frontend extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Frontend/views');

        FCom_Sales_Main::i()->addPaymentMethod('cc', 'FCom_PaymentCC_Frontend');
    }

    public function getName()
    {
        return 'Credit Card';
    }

    public function processPayment()
    {
        die("Credit Card payment not imlemented yet");
    }
}
