<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ShippingPlain_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main->addShippingMethod('plain', 'FCom_ShippingPlain_ShippingMethod');
    }
}
