<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ShippingUps_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main->addShippingMethod('ups', 'FCom_ShippingUps_ShippingMethod');
    }
}
