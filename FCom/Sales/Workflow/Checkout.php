<?php

/**
 * Class FCom_Sales_Workflow_Cart
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 */
class FCom_Sales_Workflow_Checkout extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerChoosesGuestCheckout',
        'customerUpdatesShippingAddress',
        'customerUpdatesShippingMethod',
        'customerUpdatesBillingAddress',
        'customerUpdatesPaymentMethod',
        'customerPlacesOrder',
        'customerCreatesAccountFromOrder',
        'customerMergesOrderToAccount',
    ];

    public function customerChoosesGuestCheckout($args)
    {
        $args['cart']->set('customer_email', $args['post']['customer_email']);
    }

    public function customerUpdatesShippingAddress($args)
    {
        $same = $args['cart']->get('same_address');
        foreach ($args['post']['shipping'] as $k => $v) {
            $args['cart']->set('shipping_' . $k, $v);
            if ($same) {
                $args['cart']->set('billing_' . $k, $v);
            }
        }
        $args['cart']->set('recalc_shipping_rates', 1);
    }

    public function customerUpdatesBillingAddress($args)
    {
        $same = $args['cart']->get('same_address');
        foreach ($args['post']['billing'] as $k => $v) {
            $args['cart']->set('billing_' . $k, $v);
            if ($same) {
                $args['cart']->set('shipping_' . $k, $v);
            }
        }
        if ($same) {
            $args['cart']->set('recalc_shipping_rates', 1);
        }
    }

    public function customerUpdatesShippingMethod($args)
    {
        $cart = $this->_getCart($args);
        if (empty($args['post']['shipping_method'])) {
            throw new BException('Shipping method not set');
        }
        $method = preg_split('#[\|/:.]#', $args['post']['shipping_method']);
        if (sizeof($method) !== 2) {
            throw new BException('Shipping method is invalid');
        }
        $cart->setShippingMethod($method[0], $method[1])->save();
    }

    public function customerUpdatesPaymentMethod($args)
    {

        $cart = $this->_getCart($args);
        if (empty($args['post']['payment_method'])) {
            throw new BException('Payment method not set');
        }
        $method = $args['post']['payment_method'];

        //var_dump($method); exit;
        $cart->set(['payment_method' => $method])->save();
    }

    public function customerPlacesOrder($args)
    {
        /** @var FCom_Sales_Model_Cart $cart */
        $cart = $this->_getCart($args);

        /** @var FCom_Sales_Model_Order $order */
        $order = $this->FCom_Sales_Model_Order->create()->importDataFromCart($cart);

        $result = [];
        if ($order->isPayable()) {
            $this->FCom_Sales_Main->workflowAction('customerPaysOnCheckout', [
                'cart' => $cart,
                'order' => $order,
                'result' => &$result,
            ]);
        }

        $cart->setStatusOrdered()->save();

        $this->BSession->set('last_order_id', $order->id());

        $args['result']['order'] = $order;
        $args['result']['success'] = true;
    }

    public function customerCreatesAccountFromOrder($args)
    {

    }

    public function customerMergesOrderToAccount($args)
    {

    }
}