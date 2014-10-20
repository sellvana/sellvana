<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Workflow_Payment extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerSubmitsPayment',
        'adminPlacesOrder',
        'adminCancelsAuthorization',
        'adminCapturesPayment',
        'adminRefundsPayment',
        'adminVoidsPayment',
        'adminChangesPaymentCustomState',
    ];

    public function customerSubmitsPayment($args)
    {
        try {
            $cart = $this->_getCart($args);
            $methods = $this->FCom_Sales_Main->getPaymentMethods();
            $methodCode = $cart->get('payment_method');
            if (empty($methods[$methodCode])) {
                throw new BException('Invalid payment method: ' . $methodCode);
            }
            $method = $methods[$methodCode];
            $method = $payment->getMethodObject();
            $result = $method->workflowCustomerSubmitsPayment($args['order']);

            $payment = $this->FCom_Sales_Model_Order_Payment->create([
                'order_id' => $args['order']->id(),

            ])->save();
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
