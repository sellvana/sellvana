<?php defined('BUCKYBALL_ROOT_DIR') || die();

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

        $locale = BLocale::i();
        $this->FCom_Admin_Model_Role->createPermission([
            'system' => $locale->_('System'),
            'system/users' => $locale->_('Manage Users'),
            'system/roles' => $locale->_('Manage Roles and Permissions'),
            'system/settings' => $locale->_('Update Settings'),
            'system/modules' => $locale->_('Manage Modules'),
            'system/templates' => $locale->_('Edit System Templates'),
            'system/backups' => $locale->_('System Backups'),
            'system/importexport' => $locale->_('Import Export'),
            'settings/FCom_Admin' => $locale->_('Admin Settings'),
            'settings/FCom_Core' => $locale->_('Core Settings'),
            'settings/FCom_Frontend' => $locale->_('Frontend Settings'),
            'settings/FCom_FrontendTheme' => $locale->_('Frontend Theme Settings'),
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
