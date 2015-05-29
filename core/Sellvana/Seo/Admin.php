<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Seo_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_Seo_Admin extends BClass
{

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'seo/urlaliases' => BLocale::i()->_('Seo Url Aliases'),
            'settings/Sellvana_Seo'   => BLocale::i()->_('Seo Settings'),
        ]);
    }
}
