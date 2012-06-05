<?php

class FCom_Checkout_Frontend_Controller_Checkout extends FCom_Frontend_Controller_Abstract
{
    public function action_checkout()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Checkout', 'active'=>true));
        $cart = FCom_Checkout_Model_Cart::i()->sessionCart()->calcTotals();
        $countries = FCom_Checkout_Model_Countries::i()->getList();
        $layout->view('checkout/checkout')->cart = $cart;
        $layout->view('checkout/checkout')->countries = $countries;
        $this->layout('/checkout/checkout');
        BResponse::i()->render();
    }

    public function action_checkout_post()
    {
        $href = BApp::href('checkout');
        BResponse::i()->redirect($href);
    }

    public function action_payment()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Payment methods', 'active'=>true));
        $layout->view('checkout/payment')->payment = array();
        $this->layout('/checkout/payment');
        BResponse::i()->render();
    }

    public function action_payment_post()
    {
        $href = BApp::href('checkout');
        BResponse::i()->redirect($href);
    }
}