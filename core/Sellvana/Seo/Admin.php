<?php

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
            'seo/urlaliases' => 'Seo Url Aliases',
            'settings/Sellvana_Seo'   => 'Seo Settings',
        ]);
    }
}
