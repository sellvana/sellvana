<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Customer_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property FCom_Admin_Controller_MediaLibrary $FCom_Admin_Controller_MediaLibrary
 */
class FCom_Customer_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'api/customers' => 'Customers',
            'api/customers/view' => 'View',
            'api/customers/update' => 'Update',
            'customers' => 'Customers',
            'customers/manage' => 'Manage',
            'customers/import' => 'Import',
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
            'view' => 'customer/dashboard/customers-list',
            'async' => true,
        ]);
    }

    public function onControllerBeforeDispatch($args)
    {

    }

}
