<?php

/**
 * Class Sellvana_PaymentStripe_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_ProductCompare_Admin extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main
            ->addPaymentMethod('stripe', 'Sellvana_PaymentStripe_PaymentMethod')
        ;

        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_PaymentStripe' => BLocale::i()->_('Payment Stripe Settings'),
        ]);
    }
}
