<?php

class FCom_CreditCard_Frontend extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Frontend/views');

        FCom_Checkout_Model_Cart::i()->addPaymentMethod('credit_card', 'FCom_CreditCard_Frontend');
    }

    public function getName()
    {
        return 'Credit Card';
    }
}
