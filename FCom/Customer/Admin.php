<?php

class FCom_Customer_Admin extends BClass
{
    public static function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission(array(
            'api/customers' => 'Customers',
            'api/customers/view' => 'View',
            'api/customers/update' => 'Update',
            'customers' => 'Customers',
            'customers/manage' => 'Manage',
            'customers/import' => 'Import',
        ));

        FCom_Admin_Controller_MediaLibrary::i()->allowFolder('storage/import/customers');
    }

    public function onGetDashboardWidgets($args)
    {
        $view = $args['view'];
        $view->addWidget('customers-list', array(
            'title' => 'Recent Customers',
            'icon' => 'group',
            'view' => 'customer/dashboard/customers-list',
            'async' => true,
        ));
    }
}
