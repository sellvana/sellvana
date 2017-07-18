<?php

/**
 * Class Sellvana_Cms_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_Cms_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_Cms' => (('CMS Settings')),
            'cms' => (('CMS')),
            'cms/pages' => (('Manage Pages')),
            'cms/blocks' => (('Manage Blocks')),
            'cms/nav' => (('Manage Navigation')),
        ]);
    }
}

