<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Customer_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property FCom_Admin_Controller_MediaLibrary $FCom_Admin_Controller_MediaLibrary
 */
class Sellvana_Customer_Admin extends BClass
{
    public function bootstrap()
    {
        $locale = BLocale::i();
        $this->FCom_Admin_Model_Role->createPermission([
            'api/customers' => $locale->_('Customers'),
            'api/customers/view' => $locale->_('View'),
            'api/customers/update' => $locale->_('Update'),
            'settings/Sellvana_Customers' => $locale->_('Customers Settings'),
            'customers' => $locale->_('Customers'),
            'customers/manage' => $locale->_('Manage'),
            'customers/import' => $locale->_('Import'),
        ]);

        $this->FCom_Admin_Controller_MediaLibrary->allowFolder('{random}/import/customers');
    }

    public function onGetDashboardWidgets($args)
    {
        /** @var FCom_Admin_View_Dashboard $view */
        $view = $args['view'];
        $view->addWidget('customers-list', [
            'title' => 'Recent Customers',
            'icon' => 'group',
            'view' => 'dashboard/customers-list',
            'async' => true,
        ]);
    }

    public function onControllerBeforeDispatch($args)
    {

    }

}
