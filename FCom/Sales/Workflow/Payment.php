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
    ];

    public function customerSubmitsPayment($args)
    {
        try {
            $payment = $this->FCom_Sales_Model_Order_Payment->create([
                'order_id' => $args['order']->id(),

            ])->save();
            $method = $payment->getMethodObject();
            $method->workflowCustomerSubmitsPayment($payment);
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
}
