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
            $methods = $this->FCom_Sales_Main->getPaymentMethods();
            $methodCode = $order->get('payment_method');
            if (empty($methods[$methodCode])) {
                throw new BException('Invalid payment method: ' . $methodCode);
            }
            $method = $methods[$methodCode];

            $result = $method->setSalesOrder($order)->payOnCheckout();

            if (!empty($result['redirect_to'])) {
                $args['result']['payment'] = $result;
            }

            $payment = $this->FCom_Sales_Model_Order_Payment->create([
                'order_id' => $order->id(),

            ])->save();
            $args['result']['payment']['model'] = $payment;
        } catch (Exception $e) {

            //TODO: handle payment exception
        }
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
