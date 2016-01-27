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
        $locale = BLocale::i();
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_Cms' => $locale->_('CMS Settings'),
            'cms' => $locale->_('CMS'),
            'cms/pages' => $locale->_('Manage Pages'),
            'cms/blocks' => $locale->_('Manage Blocks'),
            'cms/nav' => $locale->_('Manage Navigation'),
        ]);
    }
}

