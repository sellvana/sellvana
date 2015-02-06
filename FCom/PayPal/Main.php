<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PayPal_Frontend
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 */
class FCom_PayPal_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main
            ->addPaymentMethod('paypal', 'FCom_PayPal_PaymentMethod_ExpressCheckout')
            ->addCheckoutMethod('paypal', 'FCom_PayPal_Frontend_CheckoutMethod')
        ;
    }
}
