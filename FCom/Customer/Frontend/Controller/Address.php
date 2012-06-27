<?php

class FCom_Customer_Frontend_Controller_Address extends FCom_Frontend_Controller_Abstract
{
    public function authenticate($args=array())
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_index()
    {
        $customer = FCom_Customer_Model_Customer::sessionUser();
        $addresses = $customer->addresses();

        $crumbs[] = array('label'=>'Account', 'href'=>Bapp::href('customer/myaccount'));
        $crumbs[] = array('label'=>'View Addresses', 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('customer/address/list')->customer = $customer;
        $this->view('customer/address/list')->addresses = $addresses;
        $this->layout('/customer/address/list');
        BResponse::i()->render();
    }
    public function action_edit()
    {
        $layout = BLayout::i();
        $customer = FCom_Customer_Model_Customer::sessionUser();
        $id = BRequest::i()->get('id');
        $address = FCom_Customer_Model_Address::i()->load($id);

        $defaultShipping = false;
        if ($customer->default_shipping_id == $address->id) {
            $defaultShipping = true;
        }
        $defaultBilling = false;
        if ($customer->default_billing_id == $address->id) {
            $defaultBilling = true;
        }

        $countries = FCom_Geo_Model_Country::i()->orm()->find_many();
        $countriesList = '';
        foreach($countries as $country){
            $countriesList .= $country->iso.',';
        }
        $countriesList = substr($countriesList, 0, -1);

        $crumbs[] = array('label'=>'Account', 'href'=>Bapp::href('customer/myaccount'));
        $crumbs[] = array('label'=>'View Addresses', 'href'=>Bapp::href('customer/address'));
        $crumbs[] = array('label'=>'Edit Address', 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $layout->view('geo/embed')->countries = $countriesList;
        $layout->view('customer/address/edit')->address = $address;
        $layout->view('customer/address/edit')->default_shipping = $defaultShipping;
        $layout->view('customer/address/edit')->default_billing = $defaultBilling;
        $this->layout('/customer/address/edit');
        BResponse::i()->render();
    }

    public function action_address_post()
    {
        $customer = FCom_Customer_Model_Customer::sessionUser();
        $r = BRequest::i()->post();

        //create new address if shipping address not equal to billing
        if ($r){
            if (!empty($r['id'])) {
                $address = FCom_Customer_Model_Address::i()->load($r['id']);
            } else {
                $address = FCom_Customer_Model_Address::i()->orm()->create();
                $address->customer_id = $customer->id();
            }
            $address->set($r);
            $address->save();
            if (!empty($r['address_default_shipping'])) {
                $customer->default_shipping_id = $address->id();
            }
            if (!empty($r['address_default_billing'])) {
                $customer->default_billing_id = $address->id();
            }
            $customer->save();
        }

        $href = BApp::href('customer/address');
        BResponse::i()->redirect($href);
    }
}