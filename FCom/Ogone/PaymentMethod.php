<?php

class FCom_Ogone_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    public function getName()
    {
        return 'Ogone';
    }

    public function payOnCheckout()
    {
        die("Ogone payment not imlemented yet");
    }
}
