<?php

/**
 * Class Sellvana_Sales_Workflow_Cart
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_Sales_Workflow_Checkout extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_customerStartsCheckout($args)
    {
        // TODO: figure out for virtual orders ($c->isShippable())
        $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();

        if ($cart->hasCompleteAddress('shipping')) {
            return;
        }

        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        if ($customer) {
            $cart->importAddressesFromCustomer($customer)
                ->calculateTotals()
                ->save();
        }
    }

    public function action_customerChoosesGuestCheckout($args)
    {
        $args['cart']->set('customer_email', $args['post']['customer_email']);
    }

    public function action_customerUpdatesShippingAddress($args)
    {
        if (!empty($args['post']['shipping'])) {
            $cart = $args['cart'];
            $recalc = false;
            $same = $cart->get('same_address');
            foreach ($args['post']['shipping'] as $k => $v) {
                if ($cart->get('shipping_' . $k) !== $v) {
                    $cart->set('shipping_' . $k, $v);
                    $recalc = true;
                }
                if ($same) {
                    $cart->set('billing_' . $k, $v);
                }
            }
            if ($recalc) {
                $cart->set('recalc_shipping_rates', 1);
            }
        }
    }

    public function action_customerUpdatesBillingAddress($args)
    {
        if (!empty($args['post']['billing'])) {
            $cart = $args['cart'];
            $recalc = false;
            $same = $cart->get('same_address');
            foreach ($args['post']['billing'] as $k => $v) {
                $cart->set('billing_' . $k, $v);
                if ($same && $cart->get('shipping_' . $k) !== $v) {
                    $cart->set('shipping_' . $k, $v);
                    $recalc = true;
                }
            }
            if ($recalc) {
                $cart->set('recalc_shipping_rates', 1);
            }
        }
    }

    public function action_customerUpdatesShippingMethod($args)
    {
        $cart = $this->_getCart($args);
        if (empty($args['post']['shipping_method'])) {
            throw new BException('Shipping method not set');
        }
        $method = preg_split('#[\|/:.]#', $args['post']['shipping_method']);
        if (sizeof($method) !== 2) {
            throw new BException('Shipping method is invalid');
        }
        $cart->setShippingMethod($method[0], $method[1])->calculateTotals()->saveAllDetails();
    }

    public function action_customerUpdatesPaymentMethod($args)
    {

        $cart = $this->_getCart($args);
        if (empty($args['post']['payment_method'])) {
            throw new BException('Payment method not set');
        }

        $cart->setPaymentMethod($args['post']['payment_method']);
        $cart->setPaymentDetails($args['post']);

        $cart->save();
    }

    public function action_customerPlacesOrder($args)
    {
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = $this->_getCart($args);

        $cart->calculateTotals()->saveAllDetails();

        /** @var Sellvana_Sales_Model_Order[] $oldOrdersFromCart */
        $oldOrdersFromCart = $this->Sellvana_Sales_Model_Order->orm()->where('cart_id', $cart->id())->find_many();
        if ($oldOrdersFromCart) {
            foreach ($oldOrdersFromCart as $o) {
                $o->state()->overall()->setCanceled();
                $o->save();
            }
        }

        /** @var Sellvana_Sales_Model_Order $order */
        $order = $this->Sellvana_Sales_Model_Order->create();
        $order->importDataFromCart($cart);

        $result = [];
        if ($order->isPayable()) {
            $this->Sellvana_Sales_Main->workflowAction('customerPaysOnCheckout', [
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

    public function action_customerCreatesAccountFromOrder($args)
    {

    }

    public function action_customerMergesOrderToAccount($args)
    {

    }
}