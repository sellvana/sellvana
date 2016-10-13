<?php

class Sellvana_PaymentOgone_PaymentMethod extends Sellvana_Sales_Method_Payment_Abstract
{
    protected $_code = 'ogone';
    protected $_name = 'Ogone';

    public function getCheckoutFormView()
    {
        return $this->BLayout->getView('ogone/form');
    }

    public function payOnCheckout(Sellvana_Sales_Model_Order_Payment $payment)
    {
        die("Ogone payment not imlemented yet");
    }
}
