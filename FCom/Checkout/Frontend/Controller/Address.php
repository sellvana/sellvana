<?php

class FCom_Checkout_Frontend_Controller_Address extends FCom_Frontend_Controller_Abstract
{
    public function action_shipping()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Shipping address', 'active'=>true));
        $layout->view('checkout/address/shipping')->address = array();
        $this->layout('/checkout/address/shipping');
        BResponse::i()->render();
    }

    public function action_shipping_post()
    {
        $href = BApp::href('checkout');
        BResponse::i()->redirect($href);
    }

    public function action_billing()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Billing address', 'active'=>true));
        $layout->view('checkout/address/billing')->address = array();
        $this->layout('/checkout/address/billing');
        BResponse::i()->render();
    }

    public function action_billing_post()
    {
        $href = BApp::href('checkout');
        BResponse::i()->redirect($href);
    }
}