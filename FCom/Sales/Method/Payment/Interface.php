<?php defined('BUCKYBALL_ROOT_DIR') || die();

interface FCom_Sales_Method_Payment_Interface
{
    public function getCheckoutFormView();

    public function getCheckoutFormPrefix();

    public function setPaymentFormData(array $data);

    public function set($name, $value = null);

    public function get($name, $default = null);

    public function asArray();

    public function can($capability);

    public function getName();

    public function getSortOrder();

    public function getPublicData();

    public function getDataToSave();

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
