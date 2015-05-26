<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_Disqus_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/disqus' => 'Disqus Settings',
        ]);
    }

}
