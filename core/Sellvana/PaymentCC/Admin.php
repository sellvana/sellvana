<?php

/**
 * Class Sellvana_PaymentCC_Frontend
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_PaymentCC_Admin extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addPaymentMethod('cc', 'Sellvana_PaymentCC_PaymentMethod');
    }
}
