<?php

class FCom_Checkout extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /cart', 'FCom_Checkout_Frontend_Controller.cart')
            ->route('POST /cart', 'FCom_Checkout_Frontend_Controller.cart_post')
        ;

        BLayout::i()->allViews('Frontend/views', 'checkout/');
    }
}

