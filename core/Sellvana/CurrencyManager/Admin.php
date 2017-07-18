<?php

/**
 * Class Sellvana_CurrencyManager_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_CurrencyManager_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_CurrencyManager' => (('Currency Manager Settings')),
        ]);
    }
}