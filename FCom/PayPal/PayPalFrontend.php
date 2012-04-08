<?php

class FCom_PayPal_Frontend extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()->route('GET /paypal/.action', 'FCom_PayPal_Controller');
    }
}
