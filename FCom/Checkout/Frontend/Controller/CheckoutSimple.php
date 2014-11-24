<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Checkout_Frontend_Controller_Checkout
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 */

class FCom_Checkout_Frontend_Controller_CheckoutSimple extends FCom_Frontend_Controller_Abstract
{
    protected $_cart;

    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) {
            return false;
        }
        $this->_cart = $this->FCom_Sales_Model_Cart->sessionCart();
        if (!$this->_cart || !$this->_cart->itemQty()) {
            $this->BResponse->redirect('cart');
            return false;
        }
        return true;
    }

    public function action_index()
    {
        $c = $this->_cart;
        $step = !$c->isShippable() ? 2 : ($c->hasCompleteAddress('shipping') && $c->hasShippingMethod() ? 2 : 1);
        $this->forward('step' . $step);
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
        if (!$this->FCom_Customer_Model_Customer->isLoggedIn()) {
            $this->FCom_Sales_Main->workflowAction('customerChoosesGuestCheckout', $args);
        }
        $this->FCom_Sales_Main->workflowAction('customerUpdatesShippingAddress', $args);
    }

    public function action_step2()
    {
        $this->layout('/checkout-simple/step2');
    }

    public function action_step2__POST()
    {
        $post = $this->BRequest->post();
        $result = [];
        $args = ['post' => $post, 'cart' => $this->_cart, 'result' => &$result];
        if (!empty($post['billing'])) {
            $this->FCom_Sales_Main->workflowAction('customerUpdatesBillingAddress', $args);
        }
        $this->FCom_Sales_Main->workflowAction('customerUpdatesShippingMethod', $args);
        $this->FCom_Sales_Main->workflowAction('customerUpdatesPaymentMethod', $args);
    }

    public function action_success()
    {
        $this->layout('/checkout-simple/success');
    }

    public function action_xhr_shipping_address__POST()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->redirect('checkout');
        }

        $result = [];
        $args = ['post' => $this->BRequest->post(), 'cart' => $this->_cart, 'result' => &$result];
        $this->FCom_Sales_Main->workflowAction('customerUpdatesShippingAddress', $args);
    }

    public function action_xhr_shipping_method__POST()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->redirect('checkout');
        }

        $result = [];
        $args = ['post' => $this->BRequest->post(), 'cart' => $this->_cart, 'result' => &$result];
        $this->FCom_Sales_Main->workflowAction('customerUpdatesShippingMethod', $args);
    }

    public function action_xhr_billing_address__POST()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->redirect('checkout');
        }

        $result = [];
        $args = ['post' => $this->BRequest->post(), 'cart' => $this->_cart, 'result' => &$result];
        $this->FCom_Sales_Main->workflowAction('customerUpdatesBillingAddress', $args);
    }
}