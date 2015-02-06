<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Workflow_Payment
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Order_Payment $FCom_Sales_Model_Order_Payment
 */

class FCom_Sales_Workflow_Payment extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerPaysOnCheckout',
        'customerStartsExternalPayment',
        'customerReturnsFromExternalPayment',
        'customerCompletesCheckoutPayment',
        'customerFailsCheckoutPayment',
        'customerGetsPaymentError',

        'adminPlacesOrder',
        'adminAuthorizesPayment',
        'adminVoidsAuthorization',
        'adminReAuthorizesPayment',
        'adminCapturesPayment',
        'adminRefundsPayment',
        'adminChangesPaymentCustomState',
    ];

    public function customerPaysOnCheckout($args)
    {
        try {
            $order = $args['order'];

            $payment = $this->FCom_Sales_Model_Order_Payment->create()->importFromOrder($order);
            $result = $payment->payOnCheckout();

            $args['result']['payment'] = $result;
            $args['result']['payment']['model'] = $payment;
        } catch (Exception $e) {

            //TODO: handle payment exception
        }
    }

    public function customerStartsExternalPayment($args)
    {
        /** @var FCom_Sales_Model_Order_Payment $payment */
        $payment = $args['payment'];
        $order = $payment->order();
        $cart = $order->cart();

        $payment->state()->overall()->setExtSent();
        $cart->state()->payment()->setExternal();

        $payment->save();
        $cart->save();
    }

    public function customerReturnsFromExternalPayment($args)
    {
        /** @var FCom_Sales_Model_Order_Payment $payment */
        $payment = $args['payment'];

        $payment->state()->overall()->setExtReturned();
        $payment->save();
    }

    public function customerCompletesCheckoutPayment($args)
    {
        /** @var FCom_Sales_Model_Order_Payment $payment */
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

        $order->state()->overall()->setPlaced();

        $cart->state()->overall()->setOrdered();
        $cart->state()->payment()->setAccepted();

        $payment->save();
        $order->save();
        $cart->save();

        $payment->addHistoryEvent('complete',
            $this->BLocale->_('Customer completed payment by %s', $order->get('payment_method')),
            ['entity_id' => $payment->id()]
        );
    }

    public function customerFailsCheckoutPayment($args)
    {
        /** @var FCom_Sales_Model_Order_Payment $payment */
        $payment = $args['payment'];
        $order = $payment->order();
        $cart = $order->cart();

        $payment->state()->overall()->setFailed();
        $cart->state()->payment()->setFailed();

        $payment->save();
        $cart->save();

        $order->addHistoryEvent('failed',
            $this->BLocale->_('Customer failed payment by %s', $order->get('payment_method')),
            ['entity_id' => $payment->id()]
        );
    }

    public function customerGetsPaymentError($args)
    {
        $historyData = ['data' => $args['result']];
        /** @var FCom_Sales_Model_Order_Payment $payment */
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
                $this->BLocale->_($message),
                $historyData
            );
        } else {
            echo "<pre>"; var_dump($args); exit;
        }
    }

    public function adminPlacesOrder($args)
    {
    }

    public function adminAuthorizesPayment($args)
    {

    }

    public function adminVoidsAuthorization($args)
    {

    }

    public function adminReAuthorizesPayment($args)
    {

    }

    public function adminCapturesPayment($args)
    {

    }

    public function adminRefundsPayment($args)
    {

    }

    public function adminChangesPaymentCustomState($args)
    {
        $newState = $args['payment']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['payment']->addHistoryEvent('custom_state', 'Admin user has changed custom payment state to "' . $label . '"');
        $args['payment']->save();
    }
}
