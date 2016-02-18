<?php

/**
 * Class Sellvana_Wishlist_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_Wishlist_Admin extends BClass
{

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'wishlist' => 'Wishlist',
            'settings/Sellvana_Wishlist' => 'Wishlist Settings',
        ]);
    }
}
