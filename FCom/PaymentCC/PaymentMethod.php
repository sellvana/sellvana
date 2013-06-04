<?php

class FCom_PaymentCC_PaymentMethod extends BClass
{
    public function getName()
    {
        return 'Credit Card';
    }

    public function processPayment()
    {
        die("Credit Card payment not imlemented yet");
    }
}
