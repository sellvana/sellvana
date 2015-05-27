<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Admin
 *
 * @property FCom_Admin_Controller_MediaLibrary $FCom_Admin_Controller_MediaLibrary
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_Promo_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Controller_MediaLibrary->allowFolder('media/promo');
        $this->FCom_Admin_Model_Role->createPermission([
            'promo' => BLocale::i()->_('Promotions'),
        ]);
    }
}
