<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PaymentBasic_PaymentMethod
 */
class FCom_PaymentBasic_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    protected $_name = 'Check / Money Order';

    /**
     * @return BLayout|BView
     */
    public function getCheckoutFormView()
    {
        return $this->BLayout->view('check_mo/form');
    }

    /**
     * @param FCom_Sales_Model_Order_Payment $payment
     * @return array|mixed
     */
    public function payOnCheckout(FCom_Sales_Model_Order_Payment $payment)
    {
        // if using external checkout like paypal
        // $this->FCom_Sales_Main->workflowAction('customerStartsExternalPayment', ['payment' => $this->_payment]);

        // call this when returning from external checkout
        // $this->FCom_Sales_Main->workflowAction('customerReturnsFromExternalPayment', ['payment' => $payment]);

        $payment->state()->overall()->setPending();
        $payment->save();

        $result = [];

        return $result;
    }
}
