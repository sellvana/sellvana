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
}
