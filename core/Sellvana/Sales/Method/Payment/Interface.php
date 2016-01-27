<?php

interface Sellvana_Sales_Method_Payment_Interface
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
     * @param Sellvana_Sales_Model_Order_Payment $payment
     * @return mixed
     */
    public function payOnCheckout(Sellvana_Sales_Model_Order_Payment $payment);

    public function authorize(Sellvana_Sales_Model_Order_Payment_Transaction $transaction);

    public function reauthorize(Sellvana_Sales_Model_Order_Payment_Transaction $transaction);

    public function void(Sellvana_Sales_Model_Order_Payment_Transaction $transaction);

    public function capture(Sellvana_Sales_Model_Order_Payment_Transaction $transaction);

    public function refund(Sellvana_Sales_Model_Order_Payment_Transaction $transaction);

}
