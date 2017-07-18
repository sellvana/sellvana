<?php

/**
 * Class Sellvana_PaymentCC_Main
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_PaymentCC_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/payment_cc' => (('Payment CC Settings')),
        ]);
    }
}
