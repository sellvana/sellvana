<?php

class FCom_Checkout_Frontend_Controller_Checkout extends FCom_Frontend_Controller_Abstract
{
    public function action_checkout()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Checkout', 'active'=>true));
        $cart = FCom_Checkout_Model_Cart::i()->sessionCart()->calcTotals();

        $user = FCom_Customer_Model_Customer::sessionUser();
        $shipAddress = null;
        $billAddress = null;
        if ($user) {
            $shipAddress = $user->defaultShipping();
            $billAddress = $user->defaultBilling();
        }
        
        if (empty($shipAddress) || empty($billAddress)) {
            $href = BApp::href('customer/address/shipping');
            BResponse::i()->redirect($href);
        }

        $layout->view('checkout/checkout')->cart = $cart;
        $layout->view('checkout/checkout')->shippingAddress = FCom_Customer_Model_Address::as_html($shipAddress);
        $layout->view('checkout/checkout')->billingAddress = FCom_Customer_Model_Address::as_html($billAddress);
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

    public function action_shipping()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Shipping', 'active'=>true));
        $layout->view('checkout/shipping')->address = array();
        $layout->view('checkout/shipping')->methods = array();
        $this->layout('/checkout/shipping');
        BResponse::i()->render();
    }

    public function action_shipping_post()
    {
        $href = BApp::href('checkout/payment');
        BResponse::i()->redirect($href);
    }
}