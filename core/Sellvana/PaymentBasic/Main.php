<?php

/**
 * Class Sellvana_PaymentBasic_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_PaymentBasic_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addPaymentMethod('basic', 'Sellvana_PaymentBasic_PaymentMethod');
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_PaymentBasic' => 'Basic Payment Settings',
        ]);
    }
}
