<?php

/**
 * Class Sellvana_PaymentBasic_PaymentMethod
 */
class Sellvana_PaymentBasic_PaymentMethod extends Sellvana_Sales_Method_Payment_Abstract
{
    protected $_name = 'Check / Money Order';
    static protected $_methodKey = 'check_mo';

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
        return $this->BLayout->getView('check_mo/form');
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

        $payment->state()->overall()->setOffline();

        $this->Sellvana_Sales_Main->workflowAction('customerCompletesCheckoutPayment', ['payment' => $payment]);

        $result = [];

        return $result;
    }
}
