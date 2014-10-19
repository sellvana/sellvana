<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Checkout_Frontend_Controller_Address extends FCom_Frontend_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function action_address()
    {
        $atype = $this->BRequest->get('t');
        if (empty($atype)) {
            $atype = 'b';
        }

        $layout = $this->BLayout;
//        $countries = $this->FCom_Geo_Model_Country->orm()->find_many();
//        $countriesList = array_map(function ($el) {
//            return $el->get('iso');
//        }, $countries);
//        $countriesList = implode(',', $countriesList);
        $countries = $this->FCom_Core_Main->getAllowedCountries();
        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        if (!$cart->id()) {
            $href = $this->BApp->href('cart');
            $this->BResponse->redirect($href);
            return;
        }

        if ('s' == $atype) {
            $addressType = 'shipping';
        } else {
            $addressType = 'billing';
        }

        $address = $cart->addressAsObject($addressType);
        if (!$address->country) {
            $address->country = $this->FCom_Core_Main->getDefaultCountry();
        }
        $customer = $this->FCom_Customer_Model_Customer->sessionUser();
        if ($customer) {
            if (!$address->firstname && $customer->firstname) {
                $address->firstname = $customer->firstname;
            }
            if (!$address->lastname && $customer->lastname) {
                $address->lastname = $customer->lastname;
            }
            if (!$address->email && $customer->email) {
                $address->email = $customer->email;
            }
        }

        //$address->save();
        //$address = $this->FCom_Sales_Model_Cart_Address->load($address->id());
        if ('shipping' == $address->atype) {
            $breadCrumbLabel = $this->BLocale->_('Shipping address');
        } else {
            $breadCrumbLabel = $this->BLocale->_('Billing address');
        }
        $this->layout('/checkout/address');
        $layout->view('breadcrumbs')->set('crumbs', [
            ['label' => 'Home', 'href' =>  $this->BApp->baseUrl()],
            ['label' => 'Checkout', 'href' =>  $this->BApp->href("checkout")],
            ['label' => $breadCrumbLabel, 'active' => true]]);
//        if ($layout->view('geo/embed')) {
//            $layout->view('geo/embed')->set('countries', $countriesList);
//        }
        $layout->view('checkout/address')->set([
            'address' => $address,
            'address_type' => $atype,
            'countries' => $countries,
            'title' => $breadCrumbLabel,
        ]);
    }

    public function action_address__POST()
    {
        $r = $this->BRequest->post();

        $atype = $r['t'];
        if (empty($atype)) {
            $atype = 'b';
        }

        if ('b' == $atype || !empty($r['same_address'])) {
            $addressType = 'billing';
            $addressType2 = 'shipping';
        } else {
            $addressType = 'shipping';
            $addressType2 = 'billing';
        }

        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        if (!$cart->id()) {
            $href = $this->BApp->href('cart');
            $this->BResponse->redirect($href);
            return;
        }
        if (!$cart->validateAddress($r, [], 'address-form')) {
            $this->BResponse->redirect("checkout/address?t=" . $atype);
            return;
        }

        $cart->importAddressFromArray($r, $addressType);
        if (!empty($r['same_address']) {
            $cart->importAddressFromArray($r, $addressType2);
        }

        if (!$cart->customer_email) {
            $cart->set('customer_email', $address->email);
        }
        $cart->set('same_address', !empty($r['same_address']));
        $cart->save();

        // TODO: move this code to FCom_Customer and add the trigger for this event
        $user = $this->FCom_Customer_Model_Customer->sessionUser();
        if ($user) {
            if ('shipping' == $addressType && !$user->defaultShipping()) {
                $newAddress = $address->as_array();
                unset($newAddress['id']);
                $this->FCom_Customer_Model_Address->newShipping($newAddress, $user);
            }
            if ('billing' == $addressType && !$user->getDefaultBillingAddress()) {
                $newAddress = $address->as_array();
                unset($newAddress['id']);
                $this->FCom_Customer_Model_Address->newBilling($newAddress, $user);
            }
        }

        $href = $this->BApp->href('checkout') . '?guest=yes';
        $this->BResponse->redirect($href);
    }


}
