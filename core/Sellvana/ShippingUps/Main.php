<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_ShippingUps_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_ShippingUps_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addShippingMethod('ups', 'Sellvana_ShippingUps_ShippingMethod');
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_ShippingUps' => BLocale::i()->_('Shipping UPS Settings'),
        ]);
    }
}
