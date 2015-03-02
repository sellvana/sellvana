<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_PaymentPaypal_Frontend
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_PaymentPaypal_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main
            ->addPaymentMethod('paypal', 'Sellvana_PaymentPaypal_PaymentMethod_ExpressCheckout')
            ->addCheckoutMethod('paypal', 'Sellvana_PaymentPaypal_Frontend_CheckoutMethod')
        ;
    }
}
