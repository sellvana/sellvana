<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Blog_Main extends BClass
{
    static public function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission([
            'blog' => 'Blog',
        ]);
    }
}
