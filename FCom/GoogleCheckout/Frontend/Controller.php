<?php

class FCom_GoogleCheckout_Frontend_Controller extends BActionController
{
    public function action_redirect()
    {
    }

    public function action_return()
    {
    }

    public function action_cancel()
    {
        BResponse::i()->redirect(BConfig::i()->get('secure_url') . "/checkout");
    }

}