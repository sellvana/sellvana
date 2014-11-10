<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_ShippingUps_Main
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 */
class FCom_ShippingUps_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main->addShippingMethod('ups', 'FCom_ShippingUps_ShippingMethod');
    }
}
