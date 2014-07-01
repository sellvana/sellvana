<?php defined('BUCKYBALL_ROOT_DIR') || die();

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

    public function onGetHeaderNotifications()
    {
        if ($this->BApp->m('FCom_PushServer')->run_status === BModule::LOADED
            && $this->BConfig->get('modules/FCom_Customer/newcustomer_realtime_notification')
        ) {
            $this->FCom_PushServer_Model_Client->sessionClient()->subscribe('customers_feed');
        }
    }
}
