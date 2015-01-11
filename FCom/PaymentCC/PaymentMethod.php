<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PaymentCC_PaymentMethod
 */
class FCom_PaymentCC_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    protected $_name = 'Credit Cart';
    static protected $_methodKey = 'payment';

    /**
     * @return BLayout|BView
     */
    public function getCheckoutFormView()
    {
        return $this->BLayout->view('credit_card/form');
    }

    /**
     * @param FCom_Sales_Model_Order_Payment $payment
     * @return array
     */
    public function payOnCheckout(FCom_Sales_Model_Order_Payment $payment)
    {
        $result = [];
        return $result;
    }

    /**
     * @param FCom_Sales_Model_Order_Payment_Transaction $transaction
     * @return array
     */
    public function authorize(FCom_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $this->FCom_Sales_Main->workflowAction('customerCompletesCheckoutPayment', [
            'payment' => $this->_payment,
            'info_only' => true,
            //'auth_only' => true,
        ]);

        // call this if payment failed
        // $this->FCom_Sales_Main->workflowAction('customerFailsCheckoutPayment', ['payment' => $payment]);
        return [];
    }

    /**
     * @param FCom_Sales_Model_Order_Payment_Transaction $transaction
     * @return array
     */
    public function capture(FCom_Sales_Model_Order_Payment_Transaction $transaction)
    {
        return [];
    }
}
