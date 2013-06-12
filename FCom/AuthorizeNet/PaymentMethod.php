<?php

class FCom_AuthorizeNet_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    public function getName()
    {
        return 'Authorize.net';
    }

    public function getCheckoutFormView()
    {
        return BLayout::i()->view('ogone/form');
    }

    public function payOnCheckout()
    {
        die("Ogone payment not imlemented yet");
    }
}
