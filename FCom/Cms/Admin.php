<?php

class FCom_Cms_Admin extends BClass
{
    public static function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission([
            'cms' => 'CMS',
            'cms/pages' => 'Manage Pages',
            'cms/blocks' => 'Manage Blocks',
            'cms/nav' => 'Manage Navigation',
        ]);
    }
}

