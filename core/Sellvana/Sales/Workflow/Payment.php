<?php

/**
 * Class Sellvana_Sales_Workflow_Payment
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 * @property Sellvana_Sales_Model_Order_Payment_Transaction $Sellvana_Sales_Model_Order_Payment_Transaction
 */

class Sellvana_Sales_Workflow_Payment extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_customerPaysOnCheckout($args)
    {
        try {
            /** @var Sellvana_Sales_Model_Order $order */
            $order = $args['order'];

            /** @var Sellvana_Sales_Model_Order_Payment $payment */
            $payment = $this->Sellvana_Sales_Model_Order_Payment->create();
            $payment->importFromOrder($order);

            $order->calcItemQuantities('payments');
            $order->state()->calcAllStates();
            $order->saveAllDetails();

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

    public function action_customerGetsPaymentError($args)
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

    public function action_adminCreatesPayment($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $data = $this->BRequest->sanitize($args['data'], ['payment_method' => 'plain']);
        $amounts = isset($args['amounts']) ? $args['amounts'] : [];
        $totals = isset($args['totals']) ? $args['totals'] : [];
        $data['amount_due'] = array_sum($amounts) + array_sum($totals);
        if (!$amounts) {
            throw new BException('Please add some items to create a payment');
        }
        /** @var Sellvana_Sales_Model_Order_Payment $payment */
        $payment = $this->Sellvana_Sales_Model_Order_Payment->create($data);
        $payment->importFromOrder($order, $amounts, $totals);

        $order->calcItemQuantities('payments');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminUpdatesPayment($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $paymentId = $args['payment_id'];
        $data = $args['data'];
        $payment = $this->Sellvana_Sales_Model_Order_Payment->load($paymentId);
        if (!$payment || $payment->get('order_id') != $order->id()) {
            throw new BException('Invalid payment to update');
        }
        if (isset($data['state_custom'])) {
            $payment->state()->custom()->changeState($data['state_custom']);
        }
        if (isset($data['state_overall'])) {
            foreach ($data['state_overall'] as $state => $_) {
                $payment->state()->overall()->invokeStateChange($state);
                if ($state == 'paid') {
                    $payment->markAsPaid();
                } elseif ($state == 'refunded') {
                    $payment->markAsRefunded();
                }
            }
        }
        if (isset($data['state_processor'])) {
            foreach ($data['state_processor'] as $state => $_) {
                $payment->state()->processor()->invokeStateChange($state);
            }
        }
        $payment->save();

        $order->calcItemQuantities('payments');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminUpdatesTransaction($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $packageId = $args['package_id'];
        $data = $args['data'];
        $trans = $this->Sellvana_Sales_Model_Order_Payment_Transaction->load($packageId);
        if (!$trans || $trans->get('order_id') != $order->id()) {
            throw new BException('Invalid transaction to update');
        }
        if (isset($data['tracking_number'])) {
            $trans->set('tracking_number', $data['tracking_number']);
        }
        $trans->save();
    }

    public function action_adminDeletesPayment($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $paymentId = $args['payment_id'];
        $payment = $this->Sellvana_Sales_Model_Order_Payment->load($paymentId);
        if (!$payment || $payment->get('order_id') != $order->id()) {
            throw new BException('Invalid payment to delete');
        }
        $payment->delete();

        $order->calcItemQuantities('payments');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminReceivesOfflinePayment($args)
    {

    }

    public function action_adminAuthorizesPayment($args)
    {
        $this->_adminChangesPaymentProcessor($args);
    }

    public function action_adminVoidsAuthorization($args)
    {
        $this->_adminChangesPaymentProcessor($args);
    }

    public function action_adminReAuthorizesPayment($args)
    {
        $this->_adminChangesPaymentProcessor($args);
    }

    public function action_adminCapturesPayment($args)
    {
        $this->_adminChangesPaymentProcessor($args);
        /** @var Sellvana_Sales_Model_Order_Payment_Transaction $transaction */
        $transaction = $args['transaction'];
        $order = $transaction->payment()->order();
        $order->add('amount_captured', $transaction->get('amount'));
    }

    public function action_adminRefundsPayment($args)
    {
        $this->_adminChangesPaymentProcessor($args);
    }

    protected function _adminChangesPaymentProcessor($args)
    {
        /** @var Sellvana_Sales_Model_Order_Payment_Transaction $transaction */
        $transaction = $args['transaction'];
        $payment = $transaction->payment();
        if (!$payment) {
            throw new BException('Invalid payment');
        }

        if ($transaction->get('transaction_status') != Sellvana_Sales_Model_Order_Payment_Transaction::COMPLETED) {
            throw new BException('Transaction is not completed');
        }

        //$payment->state()->processor()->invokeAction($transaction->get('transaction_type'));
        $payment->state()->processor()->calcState();
        $payment->save();

        $order = $payment->order();
        $order->calcItemQuantities(['payments', 'refunds']);
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment[] $args
     */
    public function action_adminChangesPaymentCustomState($args)
    {
        /** @var Sellvana_Sales_Model_Order_Payment_State_Custom $newState */
        $newState = $args['payment']->state()->custom()->changeState($args['state']);
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
            $items[$paymentItem->get('order_item_id')] = $paymentItem->get('amount');
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
