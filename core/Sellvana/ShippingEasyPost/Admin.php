<?php

/**
 * Class Sellvana_ShippingEasyPost_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_ShippingEasyPost_Admin extends BClass
{
    protected $_methodCode = 'easypost';

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_ShippingEasyPost' => BLocale::i()->_('Shipping EasyPost Settings'),
        ]);
    }
}
