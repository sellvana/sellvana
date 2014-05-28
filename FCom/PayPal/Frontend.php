<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_PayPal_Frontend extends BClass
{
    static public function bootstrap()
    {
        FCom_Sales_Main::i()
            ->addPaymentMethod('paypal', 'FCom_PayPal_PaymentMethod')
            ->addCheckoutMethod('paypal', 'FCom_PayPal_Frontend_CheckoutMethod')
        ;
    }
}
