<?php

/**
 * Class Sellvana_PaymentPaypal_Frontend
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_PaymentPaypal_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main
            ->addPaymentMethod('paypal', 'Sellvana_PaymentPaypal_PaymentMethod_ExpressCheckout')
            ->addCheckoutMethod('paypal', 'Sellvana_PaymentPaypal_Frontend_CheckoutMethod')
        ;

        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_PaymentPaypal' => BLocale::i()->_('Payment PayPal Settings'),
        ]);
    }
}
