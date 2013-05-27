<?php

class FCom_GoogleCheckout_Frontend extends BClass
{
    static public function bootstrap()
    {
        BRouting::i()->route('GET /gcheckout/.action', 'FCom_PayPal_Frontend_Controller');

        BLayout::i()->addAllViews('Frontend/views');

        FCom_Checkout_Model_Cart::i()->addPaymentMethod('gcheckout', 'FCom_GoogleCheckout_Frontend');
    }

    public function getName()
    {
        return 'Google Checkout';
    }

    public function processPayment()
    {
        $href = BApp::href('gcheckout/redirect');
        BResponse::i()->redirect($href);
    }
}