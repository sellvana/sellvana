<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Checkout_Frontend_Controller_Checkout extends FCom_Frontend_Controller_Abstract
{
    protected $_authenticationFree = [
        '/checkout/login',
        '/checkout/success',
    ];

    public function authenticate($args = [])
    {
        $r = $this->BRequest;
        $isLoggedIn = $this->FCom_Customer_Model_Customer->isLoggedIn();
        if (!$isLoggedIn && $r->get('guest') != 'yes' && !in_array($r->rawPath(), $this->_authenticationFree)) {
            $this->BResponse->redirect('checkout/login');
            return false;
        } elseif ($isLoggedIn && $r->rawPath() == '/checkout/login') {
            $this->BResponse->redirect('checkout');
            return true;
        }
        return parent::authenticate($args);
    }

    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function action_checkout_login()
    {
        $layout = $this->BLayout;
        $this->layout('/checkout/login');
        $layout->view('breadcrumbs')->set('crumbs', [['label' => 'Home', 'href' => $this->BApp->baseUrl()],
            ['label' => 'Login or guest checkout', 'active' => true]]);
    }

    public function action_checkout()
    {
        $shipAddress = null;
        $billAddress = null;

        $customer = $this->FCom_Customer_Model_Customer->sessionUser();

        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        if (!$cart || !$cart->id()) {
            $this->BResponse->redirect('cart');
            return;
        }

        $shipAddress = $cart->getShippingAddress();
        $billAddress = $cart->getBillingAddress();

        if (!$shipAddress && $customer) {
            $cart->importAddressesFromCustomer($customer);
            $shipAddress = $cart->getShippingAddress();
            $billAddress = $cart->getBillingAddress();
        }

        if (empty($shipAddress) && !$cart->same_address) {
            $href = $this->BApp->href('checkout/address?t=s');
            $this->BResponse->redirect($href);
            return;
        }
        if (empty($billAddress) && !$cart->same_address) {
            $href = $this->BApp->href('checkout/address?t=b');
            $this->BResponse->redirect($href);
            return;
        }

        $cart->getShippingMethod();
        if ($customer) {
            $cart->payment_method = empty($cart->payment_method) ? $customer->getPaymentMethod() : $cart->payment_method;
            $cart->payment_details = empty($cart->payment_details) ? $customer->getPaymentDetails() : $cart->payment_details;
        }

        if (empty($cart->payment_method)) {
            $href = $this->BApp->href('checkout/payment');
            $this->BResponse->redirect($href);
            return;
        }

        $cart->calculateTotals();


        $shippingMethods = $this->FCom_Sales_Main->getShippingMethods();
        $paymentMethods = $this->FCom_Sales_Main->getPaymentMethods();
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

        $layout = $this->BLayout;
        $this->layout('/checkout/checkout');
        $layout->view('breadcrumbs')->set([
            'crumbs' => [
                ['label' => 'Home', 'href' => $this->BApp->baseUrl()],
                ['label' => 'Checkout', 'active' => true],
            ],
        ]);

        $layout->view('checkout/payment')->set('payment_methods', $paymentMethods)
                                         ->set('payment_html', $paymentMethodsHtml)
                                         ->set('cart', $cart);
/*        if (!empty($paymentMethods[$cart->payment_method])) {
            $layout->view('checkout/checkout')->set(array(
                'payment_method' => $paymentMethods[$cart->payment_method],
                'payment_details' => $this->BUtil->fromJson($cart->payment_details),
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
    }

    public function action_checkout__POST()
    {
        $post = $this->BRequest->post();
        /* @var $cart FCom_Sales_Model_Cart */
        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        if (!$cart) {
            $this->BResponse->redirect('cart');
            return;
        }

        if (!empty($post['create_account']) && $post['account']) {
            $r = $post['account'];
            //$billAddress = $cart->getBillingAddress();
            //$r['email'] = $billAddress->email;
            try {
                $modelCustomer = $this->FCom_Customer_Model_Customer;
                $modelCustomer->setSimpleRegisterRules();
                if ($modelCustomer->validate($r, [], 'checkout-register')) {
                    $customer = $this->FCom_Customer_Model_Customer->register($r);
                    $customer->login(); // make sure customer is logged in
                    $billAddress = $cart->getBillingAddress();
                    $custBillAddress = $billAddress->exportToCustomer($customer);
                    $customer->default_billing_id = $custBillAddress->id();
                    if ($cart->same_address) {
                        $customer->default_shipping_id = $custBillAddress->id();
                    } else {
                        $shipAddress = $cart->getShippingAddress();
                        $custShipAddress = $shipAddress->exportToCustomer($customer);
                        $customer->default_shipping_id = $custShipAddress->id();
                    }
                    $customer->save();
                    $cart->customer_id = $customer->id();
                    $cart->save();
                } else {
                    $this->BResponse->redirect('checkout?guest=yes');
                    return;
                }
            } catch (Exception $e) {
                //die($e->getMessage());
            }
            //$cart->coupon_code = $post['coupon_code'];
        }

        //set assisted user
        $adminUserId = $this->FCom_Admin_Model_User->sessionUserId();
        if ($adminUserId) {
            $cart->admin_id = $adminUserId;
        }

        if (!empty($post['shipping'])) {
            $shipping = explode(":", $post['shipping']);
            $cart->setShippingMethod($shipping[0]);
            $cart->shipping_service = $shipping[1];
            //$cart->shipping_price = $this->FCom_Sales_Model_Cart->getShippingMethod($post['shipping_method'])->getPrice();
        }

        if (!empty($post['payment_method'])) {
            $cart->setPaymentMethod($post['payment_method']);
        }

        if (!empty($post['payment'])) {
            $cart->payment_details = $this->BUtil->toJson($post['payment']);
            $cart->setPaymentToUser($post);
        }
        if (!empty($post['coupon_code'])) {
            $cart->coupon_code = $post['coupon_code'];
        }

        $cart->save();

        if (empty($post['place_order']) && empty($post['is_ajax'])) {
            $this->BResponse->redirect('checkout');
            return;
        }
        $order = $cart->placeOrder();
        $this->BLayout->view('email/new-order-customer')->set('order', $order)->email();
        $this->FCom_Sales_Model_Cart->resetSessionCart();

        $sData =& $this->BSession->dataToUpdate();
        $sData['last_order']['id'] = $order ? $order->id : null;
        if ($this->BRequest->get('is_ajax') || (isset($post['is_ajax']) && $post['is_ajax'])) {
            $data = $cart->getPaymentMethod()->ajaxData();
            $this->BResponse->json($data);
        } else {
            $redirectUrl = $this->BSession->get('redirect_url');
            if (!$redirectUrl) $redirectUrl = $this->BApp->href('checkout/success');
            $this->BSession->set('redirect_url', null);
            $this->BResponse->redirect($redirectUrl);
        }
    }

    public function action_payment()
    {
        $layout = $this->BLayout;
        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        $paymentMethods = $this->FCom_Sales_Main->getPaymentMethods();
        $paymentMethodsHtml = [];
        foreach ($paymentMethods as $code => $method) {
            $paymentMethodsHtml[$code] = $method->getCheckoutFormView()
                                         ->set('cart', $cart)
                                         ->set('method', $method)
                                         ->render();
        }

        $this->layout('/checkout/payment');
        $layout->view('breadcrumbs')->set('crumbs', [
            ['label' => 'Home', 'href' =>  $this->BApp->baseUrl()],
            ['label' => 'Checkout', 'href' =>  $this->BApp->href("checkout")],
            ['label' => 'Payment methods', 'active' => true]]);
        $layout->view('checkout/payment')->set('payment_methods', $paymentMethods)
                                         ->set('payment_html', $paymentMethodsHtml)
                                         ->set('cart', $cart);
    }

    public function action_payment__POST()
    {
        $post = $this->BRequest->post();
        $cart = $this->FCom_Sales_Model_Cart->sessionCart();

        if (!empty($post['payment_method'])) {
            $cart->payment_method = $post['payment_method'];
            $cart->save();
            if ($this->FCom_Customer_Model_Customer->isLoggedIn()) {
                $user = $this->FCom_Customer_Model_Customer->sessionUser();
                $user->payment_method = $post['payment_method'];
                $user->save();
            }
        }

        $href = $this->BApp->href('checkout');
        $this->BResponse->redirect($href);
    }

    public function action_shipping()
    {
        $layout = $this->BLayout;
        $this->layout('/checkout/shipping');
        $layout->view('breadcrumbs')->set('crumbs', [
            ['label' => 'Home', 'href' =>  $this->BApp->baseUrl()],
            ['label' => 'Checkout', 'href' =>  $this->BApp->href("checkout")],
            ['label' => 'Shipping address', 'active' => true]]);
        $layout->view('checkout/shipping')->set(['address' => [], 'methods' => []]);
    }

    public function action_shipping__POST()
    {
        $href = $this->BApp->href('checkout/payment');
        $this->BResponse->redirect($href);
    }

    public function action_success()
    {
        $sData =& $this->BSession->dataToUpdate();
        if (empty($sData['last_order']['id'])) {
            $this->BResponse->redirect('checkout');
            return;
        }

        $user = false;
        if ($this->BApp->m('FCom_Customer')) {
            $user = $this->FCom_Customer_Model_Customer->sessionUser();
        }

        $salesOrder = $this->FCom_Sales_Model_Order->load($sData['last_order']['id']);

        $this->layout('/checkout/success');
        $this->view('breadcrumbs')->set('crumbs', [
            ['label' => 'Home', 'href' =>  $this->BApp->baseUrl()],
            ['label' => 'Confirmation', 'active' => true],
        ]);
        $this->view('checkout/success')->set(['order' => $salesOrder, 'user' => $user]);
    }
}
