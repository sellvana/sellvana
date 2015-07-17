<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Customer_Frontend_Controller_Address
 *
 * @property Sellvana_Customer_Model_Address $Sellvana_Customer_Model_Address
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property FCom_Core_Main $FCom_Core_Main
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property FCom_Geo_Model_Country $FCom_Geo_Model_Country
 */
class Sellvana_Customer_Frontend_Controller_Address extends FCom_Frontend_Controller_Abstract
{
    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function authenticate($args = [])
    {
        return $this->Sellvana_Customer_Model_Customer->isLoggedIn() || $this->BRequest->rawPath() == '/login';
    }

    public function action_index()
    {
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        $addresses = $customer->getAddresses();

        $crumbs[] = ['label' => 'Account', 'href' => $this->BApp->href('customer/myaccount')];
        $crumbs[] = ['label' => 'View Addresses', 'active' => true];
        $this->layout('/customer/address/list');
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('customer/address/list')->customer = $customer;
        $this->view('customer/address/list')->addresses = $addresses;
    }

    public function action_edit()
    {
        /*$layout = $this->BLayout;*/
        $customer        = $this->Sellvana_Customer_Model_Customer->sessionUser();
        $id              = $this->BRequest->get('id');
        $defaultShipping = false;
        $defaultBilling  = false;
        if ($id) {
            $address = $this->Sellvana_Customer_Model_Address->load($id);
            if ($address && $address->customer_id !== $customer->id()) {
                $this->forward(false);
                return;
            }
        }

        if (!empty($address)) {
            if ($customer->default_shipping_id == $address->id) {
                $defaultShipping = true;
            }
            if ($customer->default_billing_id == $address->id) {
                $defaultBilling = true;
            }
        } else {
            $address = $this->Sellvana_Customer_Model_Address->create();
        }

        $countries = $this->FCom_Core_Main->getAllowedCountries();

        $countriesList = array_map(function ($el) {
            return $el[0];
        }, $countries);
        $countriesList = implode(',', $countriesList);

        /*$crumbs[] = array('label'=>'Account', 'href'=>$this->BApp->href('customer/myaccount'));
        $crumbs[] = array('label'=>'View Addresses', 'href'=>$this->BApp->href('customer/address'));
        $crumbs[] = array('label'=>'Edit Address', 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $layout->view('customer/address/edit')->countries = $this->FCom_Geo_Model_Country->options($countriesList);
        $layout->view('customer/address/edit')->address = $address;
        $layout->view('customer/address/edit')->default_shipping = $defaultShipping;
        $layout->view('customer/address/edit')->default_billing = $defaultBilling;*/

        //$this->view('geo/embed')->set('countries', $countriesList);
        $varSet = [
            'countries'        => $this->BLocale->getAvailableRegions('name', $countriesList),
            'address'          => $address,
            'default_shipping' => $defaultShipping,
            'default_billing'  => $defaultBilling,
            'formId'           => 'address-form',
        ];
        $this->layout('/customer/address/edit');
        $this->view('customer/address/edit')->set($varSet);
    }

    public function action_edit__POST()
    {
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        $r        = $this->BRequest;
        $response = $this->BResponse;
        $id       = $r->param('id', true);
        $post     = $r->post();
        $formId   = 'address-form';
        try {
            if ($id) {
                $address = $this->Sellvana_Customer_Model_Address->load($id);
                //check this address is belong to this user
                if ($address && $address->customer_id != $customer->id()) {
                    $this->message('You don\'t have permission to update this address', 'error');
                    $this->forward('unauthorized');
                    return;
                }
            } else {
                $address = $this->Sellvana_Customer_Model_Address->create(['customer_id' => $customer->id()]);
            }
            if ($address->validate($post, [], $formId)) {
                $address->set($post)->save();
                //update customer
                $numAddresses = $this->Sellvana_Customer_Model_Address->orm()->where('customer_id', $customer->id())->count();
                if (!empty($post['address_default_shipping']) || $numAddresses === 1) {
                    $customer->default_shipping_id = $address->id();
                }
                if (!empty($post['address_default_billing']) || $numAddresses === 1) {
                    $customer->default_billing_id = $address->id();
                }
                $customer->save();
                $this->message('Address saved successful');
                $response->redirect($this->BApp->href('customer/address'));
            } else {
                $this->message('Invalid address data, please fix above errors.', 'error', 'validator-errors:' . $formId);
                $this->formMessages($formId);
                $response->redirect($this->BApp->href('customer/address/edit') . ($id ? '?id=' . $id : ''));
            }
        } catch (Exception $e) {
            $this->BDebug->logException($e);
            $this->message($e->getMessage(), 'error');
            $response->redirect($this->BApp->href('customer/address/edit') . ($id ? '?id=' . $id : ''));
        }
    }

    public function action_choose()
    {
        $type = $this->BRequest->get('t');
        $id = $this->BRequest->get('id');
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();

        if (!empty($id)) {
            /** @var Sellvana_Sales_Model_Cart $cart */
            $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();
            $address = $this->Sellvana_Customer_Model_Address->load($id);
            //you can't change address for empty cart
            if (!$cart) {
                $this->BResponse->redirect('cart');
                return;
            }
            if (!$address) {
                $this->message('Cannot find address you select, please try again', 'error');
                $this->BResponse->redirect('customer/address/choose' . '?t=' . $type);
                return;
            }
            //you can't choose address which is not belongs to you
            if ($customer->id() != $address->get('customer_id')) {
                $this->message('You can\'t choose address which is not belongs to you', 'error');
                $this->BResponse->redirect('checkout');
                return;
            }
            if ('s' == $type) {
                $customer->default_shipping_id = $address->id();
                $customer->default_shipping    = $address;
                $cart->importAddressFromObject($address, 'shipping');
            } else {
                $customer->default_billing_id = $address->id();
                $customer->default_billing    = $address;
                $cart->importAddressFromObject($address, 'billing');
            }
            $customer->save();

            $this->BResponse->redirect('checkout');
        }

        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        $addresses = $customer->getAddresses();
        if ('s' == $type) {
            $label = "Choose shipping address";
        } else {
            $label = "Choose billing address";
        }

        $crumbs[] = ['label' => 'Checkout', 'href' => $this->BApp->href('checkout')];
        $crumbs[] = ['label' => $label, 'active' => true];
        $this->layout('/customer/address/choose');
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('customer/address/choose')->set(
            [
                'type'      => $type,
                'header'    => $label,
                'customer'  => $customer,
                'addresses' => $addresses,
            ]
        );
    }

    public function action_delete()
    {
        $id = $this->BRequest->get('id');
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        $addresses = $customer->getAddresses();
        foreach ($addresses as $addr) {
            if ($addr->get('id') == $id) {
                $address = $this->Sellvana_Customer_Model_Address->load($id);
                $address->delete();
            }
        }
        $this->BResponse->redirect($this->BApp->href('customer/address'));
    }
}
