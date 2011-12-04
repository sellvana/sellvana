<?php

class FCom_Checkout extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /cart', 'FCom_Checkout_Controller_Frontend.cart')
            ->route('POST /cart', 'FCom_Checkout_Controller_Frontend.cart_post')
        ;

        BLayout::i()->allViews('views_frontend', 'checkout/');
    }
}

