<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Admin_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_User;

        $this->FCom_Admin_Model_Role->createPermission([
            'system/users' => 'Manage Users',
            'system/roles' => 'Manage Roles and Permissions',
            'system/settings' => 'Update Settings',
            'system/modules' => 'Manage Modules',
            'system/templates' => 'Edit System Templates',
            'system/backups' => 'System Backups',
            'system/importexport' => 'Import Export',
        ]);
    }

    public function href($url = '')
    {
        return $this->BApp->adminHref($url);
    }

    public function frontendHref($url = '')
    {
        return $this->BApp->frontendHref($url);
    }
}
