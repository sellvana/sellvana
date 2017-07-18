<?php

/**
 * Class FCom_Admin_Main
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class FCom_Admin_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_User;

        $this->FCom_Admin_Model_Role->createPermission([
            'system' => (('System')),
            'system/users' => (('Manage Users')),
            'system/roles' => (('Manage Roles and Permissions')),
            'system/settings' => (('Update Settings')),
            'system/modules' => (('Manage Modules')),
            'system/templates' => (('Edit System Templates')),
            'system/backups' => (('System Backups')),
            'system/importexport' => (('Import Export')),
            'settings/FCom_Admin' => (('Admin Settings')),
            'settings/FCom_Core' => (('Core Settings')),
            'settings/FCom_Frontend' => (('Frontend Settings')),
            'settings/FCom_FrontendTheme' => (('Frontend Theme Settings')),
        ]);
    }

    /**
     * shortcut for $this->BApp->adminHref
     * @param string $url
     * @return string
     */
    public function href($url = '')
    {
        return $this->BApp->adminHref($url);
    }

    /**
     * shortcut for $this->BApp->frontendHref
     * @param string $url
     * @return string
     */
    public function frontendHref($url = '')
    {
        return $this->BApp->frontendHref($url);
    }

    public function onOAuthAfterGetAccessToken($args)
    {
        $userId = $args['token_model']->get('admin_id');
        $hlp = $this->FCom_Admin_Model_User;
        if ($userId && !$hlp->isLoggedIn()) {
            $user = $hlp->load($userId)->login();
        }
    }
}
