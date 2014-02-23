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
        $countriesList = array_map(function ($el) {
            return $el->get('iso');
        }, $countries);
        $countriesList = implode(',', $countriesList);
        $countries = FCom_Geo_Model_Country::options($countriesList);
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        if (!$cart->id()){
            $href = BApp::href('cart');
            BResponse::i()->redirect($href);
            return;
        }

        if ('s' == $atype) {
            $addressType = 'shipping';
        } else {
            $addressType = 'billing';
        }

        $address = FCom_Sales_Model_Cart_Address::i()->orm()->where("cart_id",$cart->id())->where('atype',$addressType)->find_one();
        if (!$address) {
            $address = FCom_Sales_Model_Cart_Address::i()->orm()->create();
            $address->cart_id = $cart->id();
            if ($atype == 's') {
                $address->atype = 'shipping';
            } else {
                $address->atype = 'billing';
            }
        }

        //$address->save();
        //$address = FCom_Sales_Model_Cart_Address::i()->load($address->id());
        if ('shipping' == $address->atype) {
            $breadCrumbLabel = 'Shipping address';
        } else {
            $breadCrumbLabel = 'Billing address';
        }
        $layout->view('breadcrumbs')->set('crumbs', array(
            array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Checkout', 'href'=>  BApp::href("checkout")),
            array('label'=>$breadCrumbLabel, 'active'=>true)));
        if ($layout->view('geo/embed')) {
            $layout->view('geo/embed')->set('countries', $countriesList);
        }
        $layout->view('checkout/address')->set(array('address' => $address, 'address_type' => $atype, 'countries' => $countries));
        $this->layout('/checkout/address');
    }

    public function action_address__POST()
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

        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        if (!$cart->id()){
            $href = BApp::href('cart');
            BResponse::i()->redirect($href);
            return;
        }
        /* @var FCom_Sales_Model_Cart_Address $address */
        $address = $cart->getAddressByType($addressType);
        if (!$address) {
            $address = FCom_Sales_Model_Cart_Address::i()->orm()->create();
        }
        if(!$address->validate($r, array(), 'address-form')) {
            BResponse::i()->redirect("checkout/address?t=". $atype);
            return;
        }

        if ($address) {
            $address->set($r);
            $address->atype = $addressType;
            $address->cart_id = $cart->id();
            $address->save();
        }

        if ($r['address_equal']) {
            //copy shipping address to billing address
            $addressCopy = $cart->getAddressByType($addressType2);
            if (!$addressCopy) {
                $addressCopy = FCom_Sales_Model_Cart_Address::i()->orm()->create();
                $addressCopy->cart_id = $cart->id();
            }
            $addressCopy->set($r);
            $addressCopy->atype = $addressType2;
            $addressCopy->save();
        }

        if (BApp::m('FCom_Customer')) {
            //todo move this code to FCom_Customer and add the trigger for this event
            $user = FCom_Customer_Model_Customer::i()->sessionUser();
            if ('shipping' == $addressType) {
                if ($user && !$user->defaultShipping()) {
                    $newAddress = $address->as_array();
                    unset($newAddress['id']);
                    FCom_Customer_Model_Address::i()->newShipping($newAddress, $user);
                }
            }

            if ('billing' == $addressType) {
                if ($user && !$user->defaultBilling()) {
                    $newAddress = $address->as_array();
                    unset($newAddress['id']);
                    FCom_Customer_Model_Address::i()->newBilling($newAddress, $user);
                }
            }
        }

        $href = BApp::href('checkout').'?guest=yes';
        BResponse::i()->redirect($href);
    }


}
