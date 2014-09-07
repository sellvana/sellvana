<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_PayPal_Frontend extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main
            ->addPaymentMethod('paypal', 'FCom_PayPal_PaymentMethod')
            ->addCheckoutMethod('paypal', 'FCom_PayPal_Frontend_CheckoutMethod')
        ;
    }
}
