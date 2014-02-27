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
        /*$layout = BLayout::i();*/
        $customer        = FCom_Customer_Model_Customer::i()->sessionUser();
        $id              = BRequest::i()->get('id');
        $defaultShipping = false;
        $defaultBilling  = false;

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

        /*$crumbs[] = array('label'=>'Account', 'href'=>Bapp::href('customer/myaccount'));
        $crumbs[] = array('label'=>'View Addresses', 'href'=>Bapp::href('customer/address'));
        $crumbs[] = array('label'=>'Edit Address', 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $layout->view('customer/address/edit')->countries = FCom_Geo_Model_Country::options($countriesList);
        $layout->view('customer/address/edit')->address = $address;
        $layout->view('customer/address/edit')->default_shipping = $defaultShipping;
        $layout->view('customer/address/edit')->default_billing = $defaultBilling;*/

        $this->view('geo/embed')->set('countries', $countriesList);
        $varSet = array(
            'countries'        => FCom_Geo_Model_Country::options($countriesList),
            'address'          => $address,
            'default_shipping' => $defaultShipping,
            'default_billing'  => $defaultBilling,
            'formId'           => 'address-form',
        );
        $this->view('customer/address/edit')->set($varSet);
        $this->layout('/customer/address/edit');
    }

    public function action_edit__POST()
    {
        $customer = FCom_Customer_Model_Customer::i()->sessionUser();
        $r        = BRequest::i();
        $response = BResponse::i();
        $id       = $r->param('id', true);
        $post     = $r->post();
        $formId   = 'address-form';
        try {
            if ($id) {
                $address = FCom_Customer_Model_Address::i()->load($id);
            } else {
                $address = FCom_Customer_Model_Address::i()->orm()->create();
                $address->customer_id = $customer->id();
            }
            //check this address is belong to this user
            if ($id && $address && $address->customer_id != $customer->id()) {
                $this->message('You don\'t have permission to update this address', 'error');
                //$response->redirect('unauthorized');
            }
            if ($address->validate($post, array(), $formId)) {
                $address->set($post)->save();
                //update customer
                if (!empty($post['address_default_shipping'])) {
                    $customer->default_shipping_id = $address->id();
                }
                if (!empty($post['address_default_billing'])) {
                    $customer->default_billing_id = $address->id();
                }
                $customer->save();
                $this->message('Address saved successful');
                $response->redirect(BApp::href('customer/address'));
            } else {
                $this->message('Invalid address data, please fix above errors.', 'error', 'validator-errors:'.$formId);
                $this->formMessages($formId);
                $response->redirect(BApp::href('customer/address/edit').($id ? '?id='.$id : ''));
            }
        } catch (Exception $e) {
            BDebug::logException($e);
            $this->message($e->getMessage(), 'error');
            $response->redirect(BApp::href('customer/address/edit').($id ? '?id='.$id : ''));
        }
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
                BResponse::i()->redirect('cart');
                return;
            }
            if (!$address) {
                $this->message('Cannot find address you select, please try again', 'error');
                BResponse::i()->redirect('customer/address/choose' . '?t=' . $type);
                return;
            }
            //you can't choose address which is not belongs to you
            if ($customer->id() != $address->get('customer_id')) {
                $this->message('You can\'t choose address which is not belongs to you', 'error');
                BResponse::i()->redirect('checkout');
                return;
            }
            if ('s' == $type) {
                $customer->default_shipping_id = $address->id();
                $customer->default_shipping    = $address;
                $cart->setAddressByType('shipping', $address);
                //FCom_Sales_Model_Cart_Address::i()->newShipping($cart->id(), $customer->defaultShipping());
            } else {
                $customer->default_billing_id = $address->id();
                $customer->default_billing    = $address;
                $cart->setAddressByType('billing', $address);
                //FCom_Sales_Model_Cart_Address::i()->newBilling($cart->id(), $customer->defaultBilling(), $customer->email);
            }
            $customer->save();

            BResponse::i()->redirect('checkout');
        }

        $customer = FCom_Customer_Model_Customer::i()->sessionUser();
        $addresses = $customer->addresses();
        if ('s' == $type) {
            $label = "Choose shipping address";
        } else {
            $label = "Choose billing address";
        }

        $crumbs[] = array('label'=>'Checkout', 'href'=>Bapp::href('checkout'));
        $crumbs[] = array('label'=>$label, 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('customer/address/choose')->set(
            array(
                'type'      => $type,
                'header'    => $label,
                'customer'  => $customer,
                'addresses' => $addresses,
            )
        );
        $this->layout('/customer/address/choose');
    }
}
