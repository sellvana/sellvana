<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_ShippingEasyPost_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_ShippingEasyPost_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addShippingMethod('easypost', 'Sellvana_ShippingEasyPost_ShippingMethod');
    }
}
