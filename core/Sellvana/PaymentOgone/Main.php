<?php

/**
 * Class Sellvana_PaymentOgone_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */

class Sellvana_PaymentOgone_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main
            ->addPaymentMethod('ogone', 'Sellvana_PaymentOgone_PaymentMethod')
            ->addCheckoutMethod('ogone', 'Sellvana_PaymentOgone_CheckoutMethod')
        ;

        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_PaymentOgone' => BLocale::i()->_('Payment Ogone Settings'),
        ]);
    }
}
