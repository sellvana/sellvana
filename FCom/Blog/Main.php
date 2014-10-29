<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Blog_Main
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class FCom_Blog_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'blog' => 'Blog',
        ]);
    }
}
