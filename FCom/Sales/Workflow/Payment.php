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
        'customerCompletesPayment',
        'customerFailsPayment',
        'customerGetsPaymentError',

        'adminPlacesOrder',
        'adminCancelsAuthorization',
        'adminCapturesPayment',
        'adminRefundsPayment',
        'adminVoidsPayment',
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

        $payment->state()->overall()->setProcessing();
        $payment->state()->processor()->setExtRedirected();

        $order->state()->payment()->setProcessing();

        $cart->state()->payment()->setExternal();

        $payment->save();
        $order->save();
        $cart->save();
    }

    public function customerReturnsFromExternalPayment($args)
    {
        /** @var FCom_Sales_Model_Order_Payment $payment */
        $payment = $args['payment'];

        $payment->state()->processor()->setExtReturned();
        $payment->save();
    }

    public function customerCompletesPayment($args)
    {
        /** @var FCom_Sales_Model_Order_Payment $payment */
        $payment = $args['payment'];
        $order = $payment->order();
        $cart = $order->cart();
        $infoOnly = !empty($args['info_only']); // for payment methods like COD/check/MO/PO
        $authOnly = !empty($args['auth_only']);

        if ($infoOnly) {
            $payment->state()->overall()->setPending();
            $payment->state()->processor()->setNA();
            $order->state()->payment()->setOutstanding();
        } else {
            $payment->state()->overall()->setPaid();

            if ($authOnly) {
                $payment->state()->processor()->setAuthorized();
                $order->state()->payment()->setProcessing();
            } else {
                $payment->state()->processor()->setCaptured();
                $order->state()->payment()->setPaid();
            }
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

    public function customerFailsPayment($args)
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
        /** @var FCom_Sales_Model_Order_Payment $payment */
        $payment = $args['payment'];
        $order = $payment->order();
        $cart = $order->cart();

        $payment->state()->overall()->setFailed();
        $payment->state()->processor()->setError();

        $cart->state()->payment()->setFailed();

        $payment->save();
        $cart->save();

        $order->addHistoryEvent('error',
            $this->BLocale->_('Customer received payment error from %s', $order->get('payment_method')),
            ['entity_id' => $payment->id()]
        );
    }

    public function adminPlacesOrder($args)
    {
    }

    public function adminCancelsAuthorization($args)
    {
    }

    public function adminCapturesPayment($args)
    {
    }

    public function adminRefundsPayment($args)
    {
    }

    public function adminVoidsPayment($args)
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
