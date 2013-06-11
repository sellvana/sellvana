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
        $layout->view('breadcrumbs')->crumbs = array(array('label'=>'Home', 'href'=>BApp::baseUrl()),
            array('label'=>'Checkout', 'active'=>true));

        $shipAddress = null;
        $billAddress = null;

        $customer = false;
        if (BModuleRegistry::isLoaded('FCom_Customer')) {
            $customer = FCom_Customer_Model_Customer::i()->sessionUser();
        }

        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        if (!$cart || !$cart->id) {
            BResponse::i()->redirect(BApp::href('cart'));
        }

        $billAddress = $cart->getAddressByType('billing');
        $shipAddress = $cart->getAddressByType('shipping');

        if (!$billAddress && $customer) {
            $cart->importAddressesFromCustomer($customer);
            $billAddress = $cart->getAddressByType('billing');
            $shipAddress = $cart->getAddressByType('shipping');
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
        if (!empty($paymentMethods[$cart->payment_method])) {
            $layout->view('checkout/checkout')->payment_method = $paymentMethods[$cart->payment_method];
            $layout->view('checkout/checkout')->payment_details = BUtil::fromJson($cart->payment_details);
        }

        $this->messages('checkout/checkout');
        $layout->view('checkout/checkout')->cart = $cart;
        $layout->view('checkout/checkout')->guest_checkout = !$customer;
        $layout->view('checkout/checkout')->shipping_address = $shipAddress;
        $layout->view('checkout/checkout')->billing_address = $billAddress;
        $layout->view('checkout/checkout')->shipping_methods = $shippingMethods;

        $layout->view('checkout/checkout')->totals = $cart->getTotals();
        $this->layout('/checkout/checkout');
    }

    public function action_checkout__POST()
    {
        $post = BRequest::i()->post();

        $cart = FCom_Sales_Model_Cart::i()->sessionCart();

        if (!empty($post['shipping'])) {
            $shipping = explode(":", $post['shipping']);
            $cart->shipping_method = $shipping[0];
            $cart->shipping_service = $shipping[1];
            //$cart->shipping_price = FCom_Sales_Model_Cart::i()->getShippingMethod($post['shipping_method'])->getPrice();
        }

        if (!empty($post['payment'])) {
            $cart->payment_details = BUtil::toJson($post['payment']);
            if (FCom_Customer_Model_Customer::isLoggedIn()) {
                $user = FCom_Customer_Model_Customer::i()->sessionUser();
                $user->setPaymentDetails($post['payment']);
            }
        }
        if (!empty($post['coupon_code'])) {
            $cart->coupon_code = $post['coupon_code'];
        }
        if (!empty($post['create_account'])) {
            $r = $post['account'];
            //$billAddress = $cart->getAddressByType('billing');
            //$r['email'] = $billAddress->email;
            try {
                $customer = FCom_Customer_Model_Customer::i()->register($r);
                $cart->user_id = $customer->id();
                $cart->save();
            } catch (Exception $e) {
                //die($e->getMessage());
            }
            //$cart->coupon_code = $post['coupon_code'];
        }
        $cart->save();

        if (!empty($post['place_order'])) {
            $shippingMethod = FCom_Sales_Model_Cart::i()->getShippingMethod($cart->shipping_method);
            $shippingServiceTitle = '';
            if (is_object($shippingMethod)) {
                $shippingServiceTitle = $shippingMethod->getService($cart->shipping_service);
            }
            //todo: create order
            //redirect to payment page
            $orderData = array();
            $orderData['cart_id'] = $cart->id();
            $orderData['user_id'] = $cart->user_id;
            $orderData['item_qty']  = $cart->item_qty;
            $orderData['subtotal']  = $cart->subtotal;
            $orderData['shipping_method'] = $cart->shipping_method;
            $orderData['shipping_service'] = $cart->shipping_service;
            $orderData['shipping_service_title'] = $shippingServiceTitle;
            $orderData['payment_method'] = $cart->payment_method;
            $orderData['payment_details'] = $cart->payment_details;
            $orderData['coupon_code'] = $cart->coupon_code;
            $orderData['tax'] = $cart->tax;
            $orderData['total_json'] = $cart->total_json;
            $orderData['balance'] = $cart->calc_balance; //grand total minus discount, which have to be paid
            $orderData['gt_base'] = $cart->calc_balance; //full grand total
            $orderData['created_dt'] = date("Y-m-d H:i:s");

            //create sales order
            $salesOrder = FCom_Sales_Model_Order::i()->load($cart->id(), 'cart_id');
            if ($salesOrder) {
                $salesOrder->update($orderData);
            } else {
                $salesOrder = FCom_Sales_Model_Order::i()->addNew($orderData);
            }
            //copy order items
            foreach ($cart->items() as $item) {
                $product = FCom_Catalog_Model_Product::i()->load($item->product_id);
                if (!$product) {
                    continue;
                }
                $orderItem = array();
                $orderItem['order_id'] = $salesOrder->id();
                $orderItem['product_id'] = $item->product_id;
                $orderItem['qty'] = $item->qty;
                $orderItem['total'] = $item->rowTotal();
                $orderItem['product_info'] = BUtil::toJson($product->as_array());

                $testItem = FCom_Sales_Model_Order_Item::i()->isItemExist($salesOrder->id(), $item->product_id);
                if ($testItem) {
                    $testItem->update($orderItem);
                } else {
                    FCom_Sales_Model_Order_Item::i()->addNew($orderItem);
                }
            }

            //copy addresses
            $shippingAddress = $cart->getAddressByType('shipping');
            if ($shippingAddress) {
                FCom_Sales_Model_Order_Address::i()->newAddress($salesOrder->id(), $shippingAddress);
            }
            $billingAddress = $cart->getAddressByType('billing');
            if ($billingAddress) {
                FCom_Sales_Model_Order_Address::i()->newAddress($salesOrder->id(), $billingAddress);
            }

            //Made payment
            $paymentMethods = FCom_Sales_Main::i()->getPaymentMethods();
            if (is_object($paymentMethods[$cart->payment_method])) {
                $paymentMethods[$cart->payment_method]->processPayment();
            }
        }

        $href = BApp::href('checkout');
        BResponse::i()->redirect($href);
    }

    public function action_payment()
    {
        $layout = BLayout::i();
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        $paymentMethods = FCom_Sales_Main::i()->getPaymentMethods();
        $layout->view('breadcrumbs')->crumbs = array(
            array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Checkout', 'href'=>  BApp::href("checkout")),
            array('label'=>'Payment methods', 'active'=>true));
        $layout->view('checkout/payment')->payment_methods = $paymentMethods;
        $layout->view('checkout/payment')->cart = $cart;
        $this->layout('/checkout/payment');
    }

    public function action_payment__POST()
    {
        $post = BRequest::i()->post();
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();

        if (!empty($post['payment_method'])) {
            $cart->payment_method = $post['payment_method'];
            $cart->save();
            if (BApp::m('FCom_Customer') && FCom_Customer_Model_Customer::isLoggedIn()) {
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

        BLayout::i()->view('email/new-bill')->set('order', $salesOrder)->email();
        $this->view('breadcrumbs')->crumbs = array(
            array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Confirmation', 'active'=>true));
        $this->view('checkout/success')->order = $salesOrder;
        $this->view('checkout/success')->user = $user;
        $this->layout('/checkout/success');
    }
}