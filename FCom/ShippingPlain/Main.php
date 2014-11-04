<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_ShippingPlain_Main
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 */
class FCom_ShippingPlain_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main->addShippingMethod('plain', 'FCom_ShippingPlain_ShippingMethod');
    }
}
