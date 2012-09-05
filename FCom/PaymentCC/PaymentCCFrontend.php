<?php

class FCom_PaymentCC_Frontend extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Frontend/views');

        FCom_Checkout_Model_Cart::i()->addPaymentMethod('credit_card', 'FCom_PaymentCC_Frontend');
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
