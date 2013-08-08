<?php

class FCom_Checkout_Frontend_Controller_Checkout extends FCom_Frontend_Controller_Abstract
{
    public function action_checkout_login()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array(array('label'=>'Home', 'href'=>BApp::baseUrl()),
            array('label'=>'Login or guest checkout', 'active'=>true));
        $this->layout('/checkout/login');
    }

    public function action_checkout()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->set(array(
            'crumbs' => array(
                array('label'=>'Home', 'href'=>BApp::baseUrl()),
                array('label'=>'Checkout', 'active'=>true),
            ),
        ));

        $shipAddress = null;
        $billAddress = null;

        $customer = FCom_Customer_Model_Customer::i()->sessionUser();

        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        if (!$cart || !$cart->id) {
            BResponse::i()->redirect(BApp::href('cart'));
        }

        $shipAddress = $cart->getAddressByType('shipping');
        $billAddress = $cart->getAddressByType('billing');

        if (!$shipAddress && $customer) {
            $cart->importAddressesFromCustomer($customer);
            $shipAddress = $cart->getAddressByType('shipping');
            $billAddress = $cart->getAddressByType('billing');
        }

        if (empty($shipAddress)) {
            $href = BApp::href('checkout/address?t=s');
            BResponse::i()->redirect($href);
        }
        if (empty($billAddress)) {
            $href = BApp::href('checkout/address?t=b');
            BResponse::i()->redirect($href);
        }

        if ($customer) {
            $cart->payment_method = empty($cart->payment_method) ? $customer->getPaymentMethod() : $cart->payment_method;
            $cart->payment_details = empty($cart->payment_details) ? $customer->getPaymentDetails() : $cart->payment_details;
        }

        if (empty($cart->payment_method)) {
            $href = BApp::href('checkout/payment');
            BResponse::i()->redirect($href);
        }

        $cart->calculateTotals();


        $shippingMethods = FCom_Sales_Main::i()->getShippingMethods();
        $paymentMethods = FCom_Sales_Main::i()->getPaymentMethods();
        $paymentMethodsHtml = array();
        foreach ($paymentMethods as $code => $method) {
            $paymentMethodsHtml[$code] = $method->getCheckoutFormView()
                                         ->set('cart', $cart)
                                         ->set('method', $method)
                                         ->render();
        }

        $layout->view('checkout/payment')->set('payment_methods', $paymentMethods)
                                         ->set('payment_html', $paymentMethodsHtml)
                                         ->set('cart', $cart);
/*        if (!empty($paymentMethods[$cart->payment_method])) {
            $layout->view('checkout/checkout')->set(array(
                'payment_method' => $paymentMethods[$cart->payment_method],
                'payment_details' => BUtil::fromJson($cart->payment_details),
            ));
        }
*/

        $this->messages('checkout/checkout');

        $layout->view('checkout/checkout')->set(array(
            'cart' => $cart,
            'guest_checkout' => !$customer,
            'shipping_address' => $shipAddress,
            'billing_address' => $billAddress,
            'shipping_methods' => $shippingMethods,
            'payment_methods' => $paymentMethods,
            'payment_html' => $paymentMethodsHtml,
            'totals' => $cart->getTotals()
        ));
        $this->layout('/checkout/checkout');
    }

    public function action_checkout__POST()
    {
        $post = BRequest::i()->post();
        /* @var $cart FCom_Sales_Model_Cart */
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();

        if (!empty($post['create_account'])) {
            $r = $post['account'];
            //$billAddress = $cart->getAddressByType('billing');
            //$r['email'] = $billAddress->email;
            try {
                $customer = FCom_Customer_Model_Customer::i()->register($r);
                $customer->login(); // make sure customer is logged in
                $cart->customer_id = $customer->id();
                $cart->save();
            } catch (Exception $e) {
                //die($e->getMessage());
            }
            //$cart->coupon_code = $post['coupon_code'];
        }

        if (!empty($post['shipping'])) {
            $shipping = explode(":", $post['shipping']);
            $cart->shipping_method = $shipping[0];
            $cart->shipping_service = $shipping[1];
            //$cart->shipping_price = FCom_Sales_Model_Cart::i()->getShippingMethod($post['shipping_method'])->getPrice();
        }

        if (!empty($post['payment'])) {
            $cart->payment_details = BUtil::toJson($post['payment']);
            $cart->setPaymentToUser();
        }
        if (!empty($post['coupon_code'])) {
            $cart->coupon_code = $post['coupon_code'];
        }

        $cart->save();

        if (empty($post['place_order'])) {
            BResponse::i()->redirect(BApp::href('checkout'));
        }
        $order = $cart->placeOrder();
        FCom_Sales_Model_Cart::i()->sessionCartId(false);

        $sData =& BSession::i()->dataToUpdate();
        $sData['last_order']['id'] = $order->id;

        BResponse::i()->redirect(BApp::href('checkout/success'));
    }

    public function action_payment()
    {
        $layout = BLayout::i();
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        $paymentMethods = FCom_Sales_Main::i()->getPaymentMethods();
        $paymentMethodsHtml = array();
        foreach ($paymentMethods as $code => $method) {
            $paymentMethodsHtml[$code] = $method->getCheckoutFormView()
                                         ->set('cart', $cart)
                                         ->set('method', $method)
                                         ->render();
        }

        $layout->view('breadcrumbs')->crumbs = array(
            array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Checkout', 'href'=>  BApp::href("checkout")),
            array('label'=>'Payment methods', 'active'=>true));
        $layout->view('checkout/payment')->set('payment_methods', $paymentMethods)
                                         ->set('payment_html', $paymentMethodsHtml)
                                         ->set('cart', $cart);
        $this->layout('/checkout/payment');
    }

    public function action_payment__POST()
    {
        $post = BRequest::i()->post();
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();

        if (!empty($post['payment_method'])) {
            $cart->payment_method = $post['payment_method'];
            $cart->save();
            if (FCom_Customer_Model_Customer::isLoggedIn()) {
                $user = FCom_Customer_Model_Customer::i()->sessionUser();
                $user->payment_method = $post['payment_method'];
                $user->save();
            }
        }

        $href = BApp::href('checkout');
        BResponse::i()->redirect($href);
    }

    public function action_shipping()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array(
            array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Checkout', 'href'=>  BApp::href("checkout")),
            array('label'=>'Shipping address', 'active'=>true));
        $layout->view('checkout/shipping')->address = array();
        $layout->view('checkout/shipping')->methods = array();
        $this->layout('/checkout/shipping');
    }

    public function action_shipping__POST()
    {
        $href = BApp::href('checkout/payment');
        BResponse::i()->redirect($href);
    }

    public function action_success()
    {
        $sData =& BSession::i()->dataToUpdate();
        if (empty($sData['last_order']['id'])) {
            BResponse::i()->redirect(BApp::href('checkout'));
        }

        $user = false;
        if (BApp::m('FCom_Customer')) {
            $user = FCom_Customer_Model_Customer::i()->sessionUser();
        }

        $salesOrder = FCom_Sales_Model_Order::i()->load($sData['last_order']['id']);

        BLayout::i()->view('email/new-order-customer')->set('order', $salesOrder)->email();
        $this->view('breadcrumbs')->set('crumbs', array(
            array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Confirmation', 'active'=>true),
        ));
        $this->view('checkout/success')->set(array('order' => $salesOrder, 'user' => $user));
        $this->layout('/checkout/success');
    }
}
