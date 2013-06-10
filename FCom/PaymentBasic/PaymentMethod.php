<?php

class FCom_PaymentBasic_PaymentMethod extends FCom_Sales_Model_PaymentMethod_Abstract
{
    public function getName()
    {
        return 'Check / Money Order';
    }

    public function getCheckoutFormView()
    {
        return BLayout::i()->view('check_mo/form');
    }

    public function processPayment()
    {
        die("Check / Money Order payment not imlemented yet");
    }
}
