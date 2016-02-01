<?php

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
        $this->FCom_Admin_Model_Role->createPermission([
            'api/customers' => 'Customers',
            'api/customers/view' => 'View',
            'api/customers/update' => 'Update',
            'settings/Sellvana_Customers' => 'Customers Settings',
            'customers' => 'Customers',
            'customers/manage' => 'Manage',
            'customers/import' => 'Import',
        ]);

        $this->FCom_Admin_Controller_MediaLibrary->allowFolder('{random}/import/customers');
    }

    public function onControllerBeforeDispatch($args)
    {

    }
}
