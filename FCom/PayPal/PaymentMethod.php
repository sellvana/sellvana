<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_PayPal_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    public function __construct()
    {
        $this->_name = 'PayPal Express Checkout';
    }

    /**
     * @return BLayout|BView
     */
    public function getCheckoutFormView()
    {
        return $this->BLayout->view('paypal/form');
    }

    public function payOnCheckout()
    {
        $href = $this->BApp->href('paypal/redirect');
        $this->BResponse->redirect($href);
    }
}
