<?php

/**
 * Class Sellvana_PaymentCC_PaymentMethod
 */
class Sellvana_PaymentCC_PaymentMethod extends Sellvana_Sales_Method_Payment_Abstract
{
    protected $_name = 'Credit Card';
    protected $_code = 'cc';

    protected $_capabilities = [
        'pay'             => 1,
        'pay_offline'     => 1,
        'pay_online'      => 1,
    ];

    /**
     * @return BLayout|BView
     */
    public function getCheckoutFormView()
    {
        return $this->BLayout->getView('credit_card/form');
    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment $payment
     * @return array
     */
    public function payOnCheckout(Sellvana_Sales_Model_Order_Payment $payment)
    {
        $result = [];

        $this->Sellvana_Sales_Main->workflowAction('customerCompletesCheckoutPayment', [
            'payment' => $payment,
        ]);

        return $result;
    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment_Transaction $transaction
     * @return array
     */
    public function authorize(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $this->Sellvana_Sales_Main->workflowAction('customerCompletesCheckoutPayment', [
            'payment' => $this->_payment,
            'info_only' => true,
            //'auth_only' => true,
        ]);

        // call this if payment failed
        // $this->Sellvana_Sales_Main->workflowAction('customerFailsCheckoutPayment', ['payment' => $payment]);
        return [];
    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment_Transaction $transaction
     * @return array
     */
    public function capture(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        return [];
    }
}
