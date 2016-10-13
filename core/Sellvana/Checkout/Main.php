<?php

/**
 * Class Sellvana_Checkout_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */

class Sellvana_Checkout_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addCheckoutMethod('default', 'Sellvana_Checkout_Frontend_CheckoutMethod');
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_Checkout' => 'Checkout Settings',
        ]);
    }
}
