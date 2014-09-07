<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Blog_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'blog' => 'Blog',
        ]);
    }
}
