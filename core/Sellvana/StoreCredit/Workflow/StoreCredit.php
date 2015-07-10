<?php

/**
 * Class Sellvana_Sales_Workflow_Cart
 *
 * @property Sellvana_StoreCredit_Model_Balance $Sellvana_StoreCredit_Model_Balance
 */
class Sellvana_StoreCredit_Workflow_StoreCredit extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_customerUpdatesPaymentMethod($args)
    {
        $cart = $this->_getCart($args);
        //$use = !empty($args['post']['store_credit']['use']);
        //$amount = !empty($args['post']['store_credit']['amount']) ? $args['post']['store_credit']['amount'] : null;
        $data = !empty($args['post']['store_credit']) ? $args['post']['store_credit'] : null;
        $cart->setData('store_credit', $data)->save();
    }

    public function action_customerPaysOnCheckout($args)
    {
        $cart = $args['cart'];
        $order = $args['order'];
        $order->setData('store_credit', $cart->getData('store_credit'));
        $use = $order->getData('store_credit/use');
        $amount = $order->getData('store_credit/amount');
        if ($use && $amount) {
            $balance = $this->Sellvana_StoreCredit_Model_Balance->load($order->get('customer_id'), 'customer_id');
            $balance->withdraw($amount);
        }
    }
}