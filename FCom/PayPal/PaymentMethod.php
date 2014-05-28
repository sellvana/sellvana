<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_PayPal_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    public function __construct()
    {
        $this->_name = 'PayPal Express Checkout';
    }

    public function getCheckoutFormView()
    {
        return BLayout::i()->view('paypal/form');
    }

    public function payOnCheckout()
    {
        $href = BApp::href('paypal/redirect');
        BResponse::i()->redirect($href);
    }
}
