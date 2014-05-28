<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Checkout_Frontend_Controller_Checkout extends FCom_Frontend_Controller_Abstract
{
    protected $_authenticationFree = [
        '/checkout/login',
        '/checkout/success',
    ];

    public function authenticate($args = [])
    {
        $r = BRequest::i();
        $isLoggedIn = FCom_Customer_Model_Customer::i()->isLoggedIn();
        if (!$isLoggedIn && $r->get('guest') != 'yes' && !in_array($r->rawPath(), $this->_authenticationFree)) {
            BResponse::i()->redirect('checkout/login');
            return;
        } elseif ($isLoggedIn && $r->rawPath() == '/checkout/login') {
            BResponse::i()->redirect('checkout');
            return;
        }
        return parent::authenticate($args);
    }

    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        BResponse::i()->nocache();

        return true;
    }

    public function action_checkout_login()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->set('crumbs', [['label' => 'Home', 'href' => BApp::baseUrl()],
            ['label' => 'Login or guest checkout', 'active' => true]]);
        $this->layout('/checkout/login');
    }

    public function action_checkout()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->set([
            'crumbs' => [
                ['label' => 'Home', 'href' => BApp::baseUrl()],
                ['label' => 'Checkout', 'active' => true],
            ],
        ]);

        $shipAddress = null;
        $billAddress = null;

        $customer = FCom_Customer_Model_Customer::i()->sessionUser();

        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        if (!$cart || !$cart->id) {
            BResponse::i()->redirect('cart');
            return;
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
            return;
        }
        if (empty($billAddress)) {
            $href = BApp::href('checkout/address?t=b');
            BResponse::i()->redirect($href);
            return;
        }

        if ($customer) {
            $cart->payment_method = empty($cart->payment_method) ? $customer->getPaymentMethod() : $cart->payment_method;
            $cart->payment_details = empty($cart->payment_details) ? $customer->getPaymentDetails() : $cart->payment_details;
        }

        if (empty($cart->payment_method)) {
            $href = BApp::href('checkout/payment');
            BResponse::i()->redirect($href);
            return;
        }

        $cart->calculateTotals();


        $shippingMethods = FCom_Sales_Main::i()->getShippingMethods();
        $paymentMethods = FCom_Sales_Main::i()->getPaymentMethods();
        $paymentMethodsHtml = [];
        if (is_array($paymentMethods)) {
            foreach ($paymentMethods as $code => $method) {
                $paymentMethodsHtml[$code] = $method->getCheckoutFormView()
                    ->set('cart', $cart)
                    ->set('method', $method)
                    ->set('code', $code)
                    ->render();
            }
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

        $layout->view('checkout/checkout')->set([
            'cart' => $cart,
            'guest_checkout' => !$customer,
            'shipping_address' => $shipAddress,
            'billing_address' => $billAddress,
            'shipping_methods' => $shippingMethods,
            'payment_methods' => $paymentMethods,
            'payment_html' => $paymentMethodsHtml,
            'totals' => $cart->getTotals()
        ]);
        $this->layout('/checkout/checkout');
    }

    public function action_checkout__POST()
    {
        $post = BRequest::i()->post();
        /* @var $cart FCom_Sales_Model_Cart */
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();

        if (!empty($post['create_account']) && $post['account']) {
            $r = $post['account'];
            //$billAddress = $cart->getAddressByType('billing');
            //$r['email'] = $billAddress->email;
            try {
                $modelCustomer = FCom_Customer_Model_Customer::i();
                $modelCustomer->setSimpleRegisterRules();
                if ($modelCustomer->validate($r, [], 'checkout-register')) {
                    $customer = FCom_Customer_Model_Customer::i()->register($r);
                    $customer->login(); // make sure customer is logged in
                    $cart->customer_id = $customer->id();
                    $cart->save();
                } else {
                    BResponse::i()->redirect('checkout?guest=yes');
                    return;
                }
            } catch (Exception $e) {
                //die($e->getMessage());
            }
            //$cart->coupon_code = $post['coupon_code'];
        }

        //set assisted user
        $adminUserId = FCom_Admin_Model_User::i()->sessionUserId();
        if ($adminUserId) {
            $cart->admin_id = $adminUserId;
        }

        if (!empty($post['shipping'])) {
            $shipping = explode(":", $post['shipping']);
            $cart->setShippingMethod($shipping[0]);
            $cart->shipping_service = $shipping[1];
            //$cart->shipping_price = FCom_Sales_Model_Cart::i()->getShippingMethod($post['shipping_method'])->getPrice();
        }

        if (!empty($post['payment_method'])) {
            $cart->setPaymentMethod($post['payment_method']);
        }

        if (!empty($post['payment'])) {
            $cart->payment_details = BUtil::toJson($post['payment']);
            $cart->setPaymentToUser($post);
        }
        if (!empty($post['coupon_code'])) {
            $cart->coupon_code = $post['coupon_code'];
        }

        $cart->save();

        if (empty($post['place_order']) && empty($post['is_ajax'])) {
            BResponse::i()->redirect('checkout');
            return;
        }
        $order = $cart->placeOrder();
        FCom_Sales_Model_Cart::i()->sessionCartId(false);

        $sData =& BSession::i()->dataToUpdate();
        $sData['last_order']['id'] = $order ? $order->id : null;
        if (BRequest::i()->get('is_ajax') || (isset($post['is_ajax']) && $post['is_ajax'])) {
            $data = $cart->getPaymentMethod()->ajaxData();
            BResponse::i()->json($data);
        } else {
            $redirectUrl = BSession::i()->get('redirect_url');
            if (!$redirectUrl) $redirectUrl = BApp::href('checkout/success');
            BSession::i()->set('redirect_url', null);
            BResponse::i()->redirect($redirectUrl);
        }
    }

    public function action_payment()
    {
        $layout = BLayout::i();
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        $paymentMethods = FCom_Sales_Main::i()->getPaymentMethods();
        $paymentMethodsHtml = [];
        foreach ($paymentMethods as $code => $method) {
            $paymentMethodsHtml[$code] = $method->getCheckoutFormView()
                                         ->set('cart', $cart)
                                         ->set('method', $method)
                                         ->render();
        }

        $layout->view('breadcrumbs')->set('crumbs', [
            ['label' => 'Home', 'href' =>  BApp::baseUrl()],
            ['label' => 'Checkout', 'href' =>  BApp::href("checkout")],
            ['label' => 'Payment methods', 'active' => true]]);
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
        $layout->view('breadcrumbs')->set('crumbs', [
            ['label' => 'Home', 'href' =>  BApp::baseUrl()],
            ['label' => 'Checkout', 'href' =>  BApp::href("checkout")],
            ['label' => 'Shipping address', 'active' => true]]);
        $layout->view('checkout/shipping')->set(['address' => [], 'methods' => []]);
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
            BResponse::i()->redirect('checkout');
            return;
        }

        $user = false;
        if (BApp::m('FCom_Customer')) {
            $user = FCom_Customer_Model_Customer::i()->sessionUser();
        }

        $salesOrder = FCom_Sales_Model_Order::i()->load($sData['last_order']['id']);

        BLayout::i()->view('email/new-order-customer')->set('order', $salesOrder)->email();
        $this->view('breadcrumbs')->set('crumbs', [
            ['label' => 'Home', 'href' =>  BApp::baseUrl()],
            ['label' => 'Confirmation', 'active' => true],
        ]);
        $this->view('checkout/success')->set(['order' => $salesOrder, 'user' => $user]);
        $this->layout('/checkout/success');
    }
}
