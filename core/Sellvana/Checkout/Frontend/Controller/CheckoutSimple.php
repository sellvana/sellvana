<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Checkout_Frontend_Controller_Checkout
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Customer_Model_Address $Sellvana_Customer_Model_Address
 */

class Sellvana_Checkout_Frontend_Controller_CheckoutSimple extends FCom_Frontend_Controller_Abstract
{
    /**
     * @var Sellvana_Sales_Model_Cart
     */
    protected $_cart;

    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) {
            return false;
        }
        if ($this->_action !== 'success') {
            $this->_cart = $this->Sellvana_Sales_Model_Cart->sessionCart();
            if (!$this->_cart || !$this->_cart->itemQty()) {
                $this->BResponse->redirect('cart');
                return false;
            }
        }
        return true;
    }

    public function action_index()
    {
        $this->Sellvana_Sales_Main->workflowAction('customerStartsCheckout');

        if ($this->_cart->hasCompleteAddress('shipping')) {
            $this->forward('step2');
        } else {
            $this->forward('step1');
        }
    }

    public function action_index__POST()
    {
        switch ((int)$this->BRequest->post('checkout_step')) {
            case 1:
                $this->forward('step1');

                break;
            case 2:
                $this->forward('step2');
                break;

            default:
                $this->BResponse->redirect('checkout');
        }
    }

    public function action_login()
    {
        $this->layout('/checkout-simple/login');
    }

    public function action_login__POST()
    {
        try {
            $r = $this->BRequest;
            /** @var Sellvana_Customer_Model_Customer $customerModel */
            $customerModel = $this->Sellvana_Customer_Model_Customer;
            $login = $r->post('login');
            if (!$login) {
                $login = $r->post();
            }
            if (!$customerModel->validate($login, $customerModel->getLoginRules(), 'frontend', true)) {
                $this->formMessages();
                $this->BResponse->redirect('checkout/login');
                return;
            }

            $user = $customerModel->authenticate($login['email'], $login['password']);
            if (!$user) {
                throw new Exception($this->_('Invalid email or password.'));
            }

            $statusResult = $user->validateCustomerStatus();
            if (empty($statusResult['allow_login'])) {
                throw new Exception($this->_($statusResult['error']['message']));
            }

            $user->login();

            if (!empty($login['remember_me'])) {
                $days = $this->BConfig->get('cookie/remember_days');
                $this->BResponse->cookie('remember_me', 1, ($days ? $days : 30) * 86400);
            }

            $this->BResponse->redirect('checkout');
        } catch (Exception $e) {
            $this->BDebug->logException($e);
            $this->message($e->getMessage(), 'error', 'frontend', ['title' => '']);
            $this->BResponse->redirect('checkout/login');
        }
    }

    public function action_step1()
    {
        $this->layout('/checkout-simple/step1');
    }

    public function action_step1__POST()
    {
        $post = $this->BRequest->post();
        $result = [];
        $args = ['post' => $post, 'cart' => $this->_cart, 'result' => &$result];
        if (!$this->Sellvana_Customer_Model_Customer->isLoggedIn()) {
            $this->Sellvana_Sales_Main->workflowAction('customerChoosesGuestCheckout', $args);
        }
        if (!$this->_cart->hasCompleteAddress('billing')) {
            $this->_cart->set('same_address', 1);
        }
        $this->Sellvana_Sales_Main->workflowAction('customerUpdatesShippingAddress', $args);

        $this->_cart->calculateTotals()->saveAllDetails();

        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        if ($customer && !$customer->getDefaultShippingAddress()) {
            $customer->addAddress($this->_cart->addressAsArray('shipping'), true);
        }

        $this->BResponse->redirect('checkout');
    }

    public function action_step2()
    {
        $this->layout('/checkout-simple/step2');
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        $addresses = $customer->getAddresses();
        $this->view('checkout-simple/step2')->addresses = $addresses;
        $this->view('checkout-simple/partial/address-book')->customer = $customer;
    }

    public function action_step2__POST()
    {
        $post = $this->BRequest->post();
        $result = [];
        $args = ['post' => $post, 'cart' => $this->_cart, 'result' => &$result];
        if (!empty($post['same_address'])) {
            $this->Sellvana_Sales_Main->workflowAction('customerUpdatesBillingAddress', $args);
        }
        $this->Sellvana_Sales_Main->workflowAction('customerUpdatesShippingMethod', $args);
        $this->Sellvana_Sales_Main->workflowAction('customerUpdatesPaymentMethod', $args);
        $this->Sellvana_Sales_Main->workflowAction('customerPlacesOrder', $args);

        if (!empty($result['redirect_to'])) {
            $href = $result['redirect_to'];
        } elseif (!empty($result['success'])) {
            $href = 'checkout/success';
        } else {
            $href = 'checkout';
        }
        $this->BResponse->redirect($href);
    }

    public function action_success()
    {
        $orderId = $this->BSession->get('last_order_id');
        if (!$orderId) {
            $this->BResponse->redirect('');
            return;
        }
        $order = $this->Sellvana_Sales_Model_Order->load($orderId);
        $custHlp = $this->Sellvana_Customer_Model_Customer;
        $this->view('checkout-simple/success')->set([
            'order' => $order,
            'email_customer' => $custHlp->load($order->get('customer_email'), 'email'),
            'sess_customer' => $custHlp->sessionUser(),
        ]);
        $this->layout('/checkout-simple/success');
    }

    public function action_changeAddress()
    {
        $type = $this->BRequest->get('type');
        $addressId = (int)$this->BRequest->get('id');
        if ($type && $addressId) {
            $address = $this->Sellvana_Customer_Model_Address->load($addressId);

            $post = ['address_id' => $addressId, $type => []];
            foreach (Sellvana_Customer_Model_Address::$fields as $field) {
                $post[$type][$field] = $address->get($field);
            }

            $result = [];
            $args = ['post' => $post, 'cart' => $this->_cart, 'result' => &$result];
            if ($type === 'shipping') {
                $this->Sellvana_Sales_Main->workflowAction('customerUpdatesShippingAddress', $args);
                $this->_cart->calculateTotals()->saveAllDetails();
            }
        }

        $this->BResponse->redirect('checkout');
    }

    public function action_xhr_shipping_address__POST()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->redirect('checkout');
            return;
        }

        $result = [];
        $args = ['post' => $this->BRequest->post(), 'cart' => $this->_cart, 'result' => &$result];
        $this->Sellvana_Sales_Main->workflowAction('customerUpdatesShippingAddress', $args);

        $this->BResponse->json([]);
    }

    public function action_xhr_billing_address__POST()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->redirect('checkout');
            return;
        }

        $result = [];
        $args = ['post' => $this->BRequest->post(), 'cart' => $this->_cart, 'result' => &$result];
        $this->Sellvana_Sales_Main->workflowAction('customerUpdatesBillingAddress', $args);

        $this->BResponse->json([]);
    }
    public function action_xhr_shipping_method__POST()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->redirect('checkout');
            return;
        }

        $result = [];
        $args = ['post' => $this->BRequest->post(), 'cart' => $this->_cart, 'result' => &$result];
        $this->Sellvana_Sales_Main->workflowAction('customerUpdatesShippingMethod', $args);

        $this->BResponse->json([]);
    }

    public function action_xhr_payment_method__POST()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->redirect('checkout');
            return;
        }

        $result = [];
        $args = ['post' => $this->BRequest->post(), 'cart' => $this->_cart, 'result' => &$result];
        $this->Sellvana_Sales_Main->workflowAction('customerUpdatesPaymentMethod', $args);

        $this->BResponse->json($result);
    }
}