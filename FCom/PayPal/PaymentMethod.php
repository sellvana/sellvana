<?php

class FCom_PayPal_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    public function getName()
    {
        return 'PayPal Express Checkout';
    }

    public function getCheckoutFormView()
    {
        return BLayout::i()->view('paypal/form');
    }

    public function payOnCheckout()
    {
        $href = BApp::href('paypal/redirect');
        BResponse::i()->redirect($href);
    }
}
