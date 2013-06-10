<?php

class FCom_Ogone_PaymentMethod extends FCom_Sales_Model_PaymentMethod_Abstract
{
    public function getName()
    {
        return 'Ogone';
    }

    public function processPayment()
    {
        die("Ogone payment not imlemented yet");
    }
}
