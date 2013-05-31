<?php

class FCom_PayPal_Frontend extends BClass
{
    static public function bootstrap()
    {
        BRouting::i()->get('/paypal/.action', 'FCom_PayPal_Frontend_Controller');

        BLayout::i()->addAllViews('Frontend/views');

        FCom_Sales_Main::i()
            ->addPaymentMethod('paypal', 'FCom_PayPal_PaymentMethod')
            ->addCheckoutMethod('paypal', 'FCom_PayPal_Frontend_CheckoutMethod')
        ;

    }

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
