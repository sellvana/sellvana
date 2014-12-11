<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Ogone_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    function __construct()
    {
        $this->_name = 'Ogone';
    }

    public function getCheckoutFormView()
    {
        return $this->BLayout->view('ogone/form');
    }

    public function payOnCheckout(FCom_Sales_Model_Order_Payment $payment)
    {
        die("Ogone payment not imlemented yet");
    }
}
