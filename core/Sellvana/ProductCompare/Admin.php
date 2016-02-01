<?php

/**
 * Class Sellvana_PaymentStripe_Main
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_PaymentStripe_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_ProductCompare' => 'Product Compare Settings',
        ]);
    }
}
