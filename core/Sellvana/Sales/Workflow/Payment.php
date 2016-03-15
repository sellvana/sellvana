<?php

/**
 * Class Sellvana_Sales_Workflow_Payment
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 */

class Sellvana_Sales_Workflow_Payment extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_customerPaysOnCheckout($args)
    {
        try {
            $order = $args['order'];

            /** @var Sellvana_Sales_Model_Order_Payment $payment */
            $payment = $this->Sellvana_Sales_Model_Order_Payment->create()->importFromOrder($order);

            $method = $payment->getMethodObject();
            $result = $method->payOnCheckout($payment);

            $args['result']['payment'] = $result;
            $args['result']['payment']['model'] = $payment;
        } catch (Exception $e) {

            //TODO: handle payment exception
        }
    }

    public function action_customerStartsExternalPayment($args)
    {
        /** @var Sellvana_Sales_Model_Order_Payment $payment */
        $payment = $args['payment'];
        $order = $payment->order();
        $cart = $order->cart();

        $payment->state()->overall()->setExtSent();
        $cart->state()->payment()->setExternal();

        $payment->save();
        $cart->save();
    }

    public function action_customerReturnsFromExternalPayment($args)
    {
        /** @var Sellvana_Sales_Model_Order_Payment $payment */
        $payment = $args['payment'];

        $payment->state()->overall()->setExtReturned();
        $payment->save();
    }

    public function action_customerCompletesCheckoutPayment($args)
    {
        /** @var Sellvana_Sales_Model_Order_Payment $payment */
        $payment = $args['payment'];
        $order = $payment->order();
        $cart = $order->cart();
        $transType = !empty($args['transaction_type']) ? $args['transaction_type'] : null;

        switch ($transType) {
            case 'order':  // for payment methods like COD/check/MO/PO or paypal EC Order method
                $payment->state()->overall()->setPending();
                $order->state()->payment()->setOutstanding();
                break;

            case 'auth':
                $payment->state()->overall()->setProcessing();
                $order->state()->payment()->setProcessing();
                break;

            case 'capture':
                $payment->state()->overall()->setPaid();
                $order->state()->payment()->setPaid();
                break;
        }

        $cart->state()->overall()->setOrdered();
        $cart->state()->payment()->setAccepted();

        $payment->save();
        $order->save();
        $cart->save();

        $payment->addHistoryEvent('complete',
            $this->_('Customer completed payment by %s', $order->getPaymentMethod()->getName()),
            ['entity_id' => $payment->id()]
        );
    }

    public function action_customerFailsCheckoutPayment($args)
    {
        /** @var Sellvana_Sales_Model_Order_Payment $payment */
        $payment = $args['payment'];
        $order = $payment->order();
        $cart = $order->cart();

        $payment->state()->overall()->setFailed();
        $cart->state()->payment()->setFailed();

        $payment->save();
        $cart->save();

        $order->addHistoryEvent('failed',
            $this->_('Customer failed payment by %s', $order->get('payment_method')),
            ['entity_id' => $payment->id()]
        );
    }

    public function customerGetsPaymentError($args)
    {
        $historyData = ['data' => $args['result']];
        /** @var Sellvana_Sales_Model_Order_Payment $payment */
        $payment = $args['payment'];
        if ($payment) {
            $order = $payment->order();
            $cart = $order->cart();

            $payment->state()->overall()->setFailed();
            //$payment->state()->processor()->setError();

            $cart->state()->payment()->setFailed();

            $payment->save();
            $cart->save();

            $message = $order->get('payment_method') . ' error: ' . $args['result']['error']['message'];
            $historyData['entity_id'] = $payment->id();

            $order->addHistoryEvent('error',
                $this->_($message),
                $historyData
            );
        } else {
            echo "<pre>"; var_dump($args); exit;
        }
    }

    public function action_adminPlacesOrder($args)
    {

    }

    public function action_adminReceivesOfflinePayment($args)
    {

    }

    public function action_adminAuthorizesPayment($args)
    {

    }

    public function action_adminVoidsAuthorization($args)
    {

    }

    public function action_adminReAuthorizesPayment($args)
    {

    }

    public function action_adminCapturesPayment($args)
    {

    }

    public function action_adminRefundsPayment($args)
    {

    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment[] $args
     */
    public function action_adminChangesPaymentCustomState($args)
    {
        $newState = $args['payment']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['payment']->addHistoryEvent('custom_state', 'Admin user has changed custom payment state to "' . $label . '"');
        $args['payment']->save();
    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment[] $args
     */
    public function action_adminMarksPaymentAsPaid($args)
    {
        $args['payment']->markAsPaid();

        $items = [];
        /** @var Sellvana_Sales_Model_Order_Payment_Item $paymentItem */
        foreach ($args['payment']->items() as $paymentItem) {
            $items[$paymentItem->get('order_item_id')] = $paymentItem->get('qty');
        }

        /** @var Sellvana_Sales_Model_Order_Item $orderItem */
        foreach ($args['payment']->order()->items() as $orderItem) {
            if (!empty($items[$orderItem->id()])) {
                $orderItem->markAsPaid($items[$orderItem->id()]);
            }
        }

        $args['payment']->order()->state()->calcAllStates();
        $args['payment']->order()->saveAllDetails();
    }
}
