<?php

class FCom_Customer_Frontend_Controller_Address extends FCom_Frontend_Controller_Abstract
{
    public function authenticate($args=array())
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_shipping()
    {
        $layout = BLayout::i();
        $user = FCom_Customer_Model_Customer::sessionUser();
        $shipAddress = $user->defaultShipping();
        if (!$shipAddress) {
            $shipAddress = FCom_Customer_Model_Address::i()->orm()->create();
            $shipAddress->customer_id = $user->id();
            $shipAddress->save();
        }
        if ($user->default_shipping_id == $user->default_billing_id) {
            $layout->view('customer/address')->address_equal = true;
        }
        $countries = FCom_Geo_Model_Country::i()->orm()->find_many();
        $countriesList = '';
        foreach($countries as $country){
            $countriesList .= $country->iso.',';
        }
        $countriesList = substr($countriesList, 0, -1);

        $layout->view('geo/embed')->countries = $countriesList;
        $layout->view('customer/address')->address = $shipAddress;
        $layout->view('customer/address')->address_type = 'shipping';
        $this->layout('/customer/address');
        BResponse::i()->render();
    }

    public function action_billing()
    {
        $layout = BLayout::i();
        $user = FCom_Customer_Model_Customer::sessionUser();
        $address = $user->defaultBilling();
        if (!$address) {
            $address = FCom_Customer_Model_Address::i()->orm()->create();
            $address->customer_id = $user->id();
            $address->save();
        }
        if ($user->default_shipping_id == $user->default_billing_id) {
            $layout->view('customer/address')->address_equal = true;
        }

        $layout->view('customer/address')->address = $address;
        $layout->view('customer/address')->address_type = 'billing';
        $this->layout('/customer/address');
        BResponse::i()->render();
    }

    public function action_address_post()
    {
        $user = FCom_Customer_Model_Customer::sessionUser();
        $r = BRequest::i()->post();

        //create new address if shipping address not equal to billing
        if (0 == $r['address_equal'] && $user->default_shipping_id == $user->default_billing_id){
            $address = FCom_Customer_Model_Address::i()->orm()->create();
            $address->customer_id = $user->id();
            $address->save();
            if ($r['address_type'] == 'shipping') {
                $user->default_shipping_id = $address->id();
            } elseif ($r['address_type'] == 'billing') {
                $user->default_billing_id = $address->id();
            }

            $user->save();
        }

        if ($r['address_type'] == 'shipping') {
            $address = $user->defaultShipping();
        } elseif ($r['address_type'] == 'billing') {
            $address = $user->defaultBilling();
        }
        if ($address) {
            $address->set($r)->save();
        }

        $href = BApp::href('customer/myaccount');
        BResponse::i()->redirect($href);
    }
}