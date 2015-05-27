<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
            'wishlist' => BLocale::i()->_('Wishlist'),
            'settings/wishlist' => BLocale::i()->_('Wishlist Settings'),
        ]);
    }
}
