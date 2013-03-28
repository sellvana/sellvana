<?php

class FCom_PaymentCheckMO_Frontend extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Frontend/views');

        FCom_Checkout_Model_Cart::i()->addPaymentMethod('check_mo', 'FCom_PaymentCheckMO_Frontend');
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
