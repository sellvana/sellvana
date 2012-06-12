<?php

class FCom_Checkout_Frontend_Controller_Address extends FCom_Frontend_Controller_Abstract
{
    public function action_address()
    {
        $atype = BRequest::i()->get('t');
        if (empty($atype)) {
            $atype = 'b';
        }

        $layout = BLayout::i();
        $countries = FCom_Geo_Model_Country::i()->orm()->find_many();
        $countriesList = '';
        foreach ($countries as $country) {
            $countriesList .= $country->iso.',';
        }
        $countriesList = substr($countriesList, 0, -1);

        $cart = FCom_Checkout_Model_Cart::i()->sessionCart();
        if (!$cart->id()){
            $href = BApp::href('cart');
            BResponse::i()->redirect($href);
        }

        if ('s' == $atype) {
            $addressType = 'shipping';
        } else {
            $addressType = 'billing';
        }

        $address = FCom_Checkout_Model_Address::i()->orm()->where("cart_id",$cart->id())->where('atype',$addressType)->find_one();
        if (!$address) {
            $address = FCom_Checkout_Model_Address::i()->orm()->create();
            $address->cart_id = $cart->id();
            if ($atype == 's') {
                $address->atype = 'shipping';
            } else {
                $address->atype = 'billing';
            }
        }

        $address->save();
        $address = FCom_Checkout_Model_Address::i()->load($address->id());
        if ('shipping' == $address->atype) {
            $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Shipping address', 'active'=>true));
        } else {
            $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Billing address', 'active'=>true));
        }
        $layout->view('geo/embed')->countries = $countriesList;
        $layout->view('checkout/address')->address = $address;
        $layout->view('checkout/address')->address_type = $atype;
        $this->layout('/checkout/address');
        BResponse::i()->render();
    }

    public function action_address_post()
    {
        $r = BRequest::i()->post();

        $atype = $r['t'];
        if (empty($atype)) {
            $atype = 'b';
        }

        if ('s' == $atype) {
            $addressType = 'shipping';
            $addressType2 = 'billing';
        } else {
            $addressType = 'billing';
            $addressType2 = 'shipping';
        }

        $cart = FCom_Checkout_Model_Cart::i()->sessionCart();
        if (!$cart->id()){
            $href = BApp::href('cart');
            BResponse::i()->redirect($href);
        }

        $address = FCom_Checkout_Model_Address::i()->getAddress($cart->id(), $addressType);
        if ($address) {
            $address->set($r);
            $address->atype = $addressType;
            $address->save();
        }

        if ($r['address_equal']) {
            //copy of shipping address for billing address
            $addressCopy = FCom_Checkout_Model_Address::i()->getAddress($cart->id(), $addressType2);
            if (!$addressCopy) {
                $addressCopy = FCom_Checkout_Model_Address::i()->orm()->create();
                $addressCopy->cart_id = $cart->id();
            }
            $addressCopy->set($r);
            $addressCopy->atype = $addressType2;
            $addressCopy->save();
        }

        $href = BApp::href('checkout');
        BResponse::i()->redirect($href);
    }

    
}