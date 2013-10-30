<?php

class FCom_PaymentCC_PaymentMethod extends BClass
{
    public function getName()
    {
        return 'Credit Card';
    }

    public function payOnCheckout()
    {
        die("Credit Card payment not imlemented yet");
    }
}
