<?php

class FCom_PaymentBasic_PaymentMethod extends BClass
{
    public function getName()
    {
        return 'Check / Money Order';
    }

    public function processPayment()
    {
        die("Check / Money Order payment not imlemented yet");
    }
}
