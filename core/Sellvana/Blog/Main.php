<?php

/**
 * Class Sellvana_Blog_Main
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_Blog_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_Blog' => (('Blog Settings')),
            'blog' => (('Blog')),
        ]);
    }
}
