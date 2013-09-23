<?php

class FCom_Customer_Frontend_Controller_Address extends FCom_Frontend_Controller_Abstract
{
    public function authenticate($args=array())
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_index()
    {
        $customer = FCom_Customer_Model_Customer::i()->sessionUser();
        $addresses = $customer->addresses();

        $crumbs[] = array('label'=>'Account', 'href'=>Bapp::href('customer/myaccount'));
        $crumbs[] = array('label'=>'View Addresses', 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('customer/address/list')->customer = $customer;
        $this->view('customer/address/list')->addresses = $addresses;
        $this->layout('/customer/address/list');
    }
    public function action_edit()
    {
        $layout = BLayout::i();
        $customer = FCom_Customer_Model_Customer::i()->sessionUser();
        $id = BRequest::i()->get('id');
        $defaultShipping = false;
        $defaultBilling = false;
        $address = false;
        if ($id) {
            $address = FCom_Customer_Model_Address::i()->load($id);
            if ($customer->default_shipping_id == $address->id) {
                $defaultShipping = true;
            }
            if ($customer->default_billing_id == $address->id) {
                $defaultBilling = true;
            }
        } else {
            $address = FCom_Customer_Model_Address::i()->create();
        }

        $countries = FCom_Geo_Model_Country::i()->orm()->find_many();

        $countriesList = array_map(function ($el) {
            return $el->get('iso');
        }, $countries);
        $countriesList = implode(',', $countriesList);

        $crumbs[] = array('label'=>'Account', 'href'=>Bapp::href('customer/myaccount'));
        $crumbs[] = array('label'=>'View Addresses', 'href'=>Bapp::href('customer/address'));
        $crumbs[] = array('label'=>'Edit Address', 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $layout->view('geo/embed')->countries = $countriesList;
        $layout->view('customer/address/edit')->countries = FCom_Geo_Model_Country::options($countriesList);
        $layout->view('customer/address/edit')->address = $address;
        $layout->view('customer/address/edit')->default_shipping = $defaultShipping;
        $layout->view('customer/address/edit')->default_billing = $defaultBilling;
        $this->layout('/customer/address/edit');
    }

    public function action_edit__POST()
    {
        $customer = FCom_Customer_Model_Customer::i()->sessionUser();
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

    public function action_choose()
    {
        $type = BRequest::get('t');
        $id = BRequest::get('id');
        $customer = FCom_Customer_Model_Customer::i()->sessionUser();

        if (!empty($id)) {
            $cart = FCom_Sales_Model_Cart::i()->sessionCart();
            $address = FCom_Customer_Model_Address::i()->load($id);
            //you can't change address for empty cart
            if (!$cart) {
                BResponse::i()->redirect(BApp::href('cart'));
            }
            if ('s' == $type) {
                $customer->default_shipping_id = $address->id();
                FCom_Sales_Model_Cart_Address::i()->newShipping($cart->id(), $customer->defaultShipping());
            } else {
                $customer->default_billing_id = $address->id();
                FCom_Sales_Model_Cart_Address::i()->newBilling($cart->id(), $customer->defaultBilling(), $customer->email);
            }
            $customer->save();

            BResponse::i()->redirect(BApp::href('checkout'));
        }

        $addresses = $customer->addresses();
        if ('s' == $type) {
            $label = "Choose shipping address";
        } else {
            $label = "Choose billing address";
        }
        $crumbs[] = array('label'=>'Checkout', 'href'=>Bapp::href('checkout'));
        $crumbs[] = array('label'=>$label, 'active'=>true);

        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('customer/address/choose')->type = $type;
        $this->view('customer/address/choose')->header = $label;
        $this->view('customer/address/choose')->customer = $customer;
        $this->view('customer/address/choose')->addresses = $addresses;
        $this->layout('/customer/address/choose');
    }
}