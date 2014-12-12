<?php defined('BUCKYBALL_ROOT_DIR') || die();

interface FCom_Sales_Method_Payment_Interface
{
    /**
     * @param FCom_Sales_Model_Order_Payment $payment
     * @return mixed
     */
    public function payOnCheckout(FCom_Sales_Model_Order_Payment $payment);

    public function authorize(FCom_Sales_Model_Order_Payment_Transaction $transaction);

    public function reauthorize(FCom_Sales_Model_Order_Payment_Transaction $transaction);

    public function void(FCom_Sales_Model_Order_Payment_Transaction $transaction);

    public function capture(FCom_Sales_Model_Order_Payment_Transaction $transaction);

    public function refund(FCom_Sales_Model_Order_Payment_Transaction $transaction);
}
