<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Cms_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'cms' => 'CMS',
            'cms/pages' => 'Manage Pages',
            'cms/blocks' => 'Manage Blocks',
            'cms/nav' => 'Manage Navigation',
        ]);
    }
}

