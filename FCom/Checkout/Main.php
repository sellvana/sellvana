<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Checkout_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main->addCheckoutMethod('default', 'FCom_Checkout_Frontend_CheckoutMethod');
    }
}
