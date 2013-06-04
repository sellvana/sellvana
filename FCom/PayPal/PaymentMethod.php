<?php

class FCom_PayPal_PaymentMethod extends BClass
{
    public function getName()
    {
        return 'PayPal Express Checkout';
    }

    public function processPayment()
    {
        $href = BApp::href('paypal/redirect');
        BResponse::i()->redirect($href);
    }
}
