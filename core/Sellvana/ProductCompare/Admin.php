<?php

/**
 * Class Sellvana_ProductCompare_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_ProductCompare_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_ProductCompare' => (('Product Compare Settings')),
        ]);
    }
}
