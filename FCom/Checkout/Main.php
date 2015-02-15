<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Checkout_Main
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 */

class FCom_Checkout_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main->addCheckoutMethod('default', 'FCom_Checkout_Frontend_CheckoutMethod');
    }
}
