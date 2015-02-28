<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_ShippingUps_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_ShippingUps_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addShippingMethod('ups', 'Sellvana_ShippingUps_ShippingMethod');
    }
}
