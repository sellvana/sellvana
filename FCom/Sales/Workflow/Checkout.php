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
        'customerStartsCheckout',
        'customerChoosesGuestCheckout',
        'customerUpdatesShippingAddress',
        'customerUpdatesShippingMethod',
        'customerUpdatesBillingAddress',
        'customerUpdatesPaymentMethod',
        'customerPlacesOrder',
        'customerCreatesAccountFromOrder',
        'customerMergesOrderToAccount',
    ];

    public function customerStartsCheckout($args)
    {
        // TODO: figure out for virtual orders ($c->isShippable())
        $cart = $this->FCom_Sales_Model_Cart->sessionCart();

        if ($cart->hasCompleteAddress('shipping')) {
            return;
        }

        $customer = $this->FCom_Customer_Model_Customer->sessionUser();
        if ($customer) {
            $cart->importAddressesFromCustomer($customer)
                ->set([
                    'recalc_shipping_rates' => 1
                ])
                ->calculateTotals()
                ->save();
        }
    }

    public function customerChoosesGuestCheckout($args)
    {

    }

    public function customerUpdatesShippingAddress($args)
    {
        if (!empty($args['post']['shipping'])) {
            $same = $args['cart']->get('same_address');
            foreach ($args['post']['shipping'] as $k => $v) {
                $args['cart']->set('shipping_' . $k, $v);
                if ($same) {
                    $args['cart']->set('billing_' . $k, $v);
                }
            }
            $args['cart']->set('recalc_shipping_rates', 1);
        }
    }

    public function customerUpdatesBillingAddress($args)
    {
        if (!empty($args['post']['billing'])) {
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
        $cart->setShippingMethod($method[0], $method[1])->calculateTotals()->save();
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

        /** @var FCom_Sales_Model_Order[] $oldOrdersFromCart */
        $oldOrdersFromCart = $this->FCom_Sales_Model_Order->orm()->where('cart_id', $cart->id())->find_many();
        if ($oldOrdersFromCart) {
            foreach ($oldOrdersFromCart as $o) {
                $o->state()->overall()->setCanceled();
                $o->save();
            }
        }

        /** @var FCom_Sales_Model_Order $order */
        $order = $this->FCom_Sales_Model_Order->create()->importDataFromCart($cart);

        $result = [];
        if ($order->isPayable()) {
            $this->FCom_Sales_Main->workflowAction('customerPaysOnCheckout', [
                'cart' => $cart,
                'order' => $order,
                'result' => &$result,
            ]);

            if (!empty($result['payment']['complete'])) {
                $cart->state()->overall()->setOrdered();
                $cart->save();
            }
            if (!empty($result['payment']['redirect_to'])) {
                $args['result']['redirect_to'] = $result['payment']['redirect_to'];
            }
        }

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