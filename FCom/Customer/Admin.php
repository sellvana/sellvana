<?php

class FCom_Customer_Admin extends BClass
{
    public static function bootstrap()
    {
        BRouting::i()
            ->get('/customers', 'FCom_Customer_Admin_Controller_Customers.index')
            ->any('/customers/.action', 'FCom_Customer_Admin_Controller_Customers')
            ->any('/customers/import/.action', 'FCom_Customer_Admin_Controller_CustomersImport')
        ;

        BLayout::i()->addAllViews('Admin/views')
            ->loadLayoutAfterTheme('Admin/layout.yml');

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
}