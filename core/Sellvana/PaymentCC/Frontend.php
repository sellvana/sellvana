<?php

/**
 * Class Sellvana_PaymentCC_Frontend
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_PaymentCC_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/payment_cc' => BLocale::i()->_('Payment CC Settings'),
        ]);
    }
}
