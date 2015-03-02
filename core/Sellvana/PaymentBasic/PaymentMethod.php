<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_PaymentBasic_PaymentMethod
 */
class Sellvana_PaymentBasic_PaymentMethod extends Sellvana_Sales_Method_Payment_Abstract
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
     * @param Sellvana_Sales_Model_Order_Payment $payment
     * @return array|mixed
     */
    public function payOnCheckout(Sellvana_Sales_Model_Order_Payment $payment)
    {
        // if using external checkout like paypal
        // $this->Sellvana_Sales_Main->workflowAction('customerStartsExternalPayment', ['payment' => $this->_payment]);

        // call this when returning from external checkout
        // $this->Sellvana_Sales_Main->workflowAction('customerReturnsFromExternalPayment', ['payment' => $payment]);

        $payment->state()->overall()->setPending();
        $payment->save();

        $result = [];

        return $result;
    }
}
