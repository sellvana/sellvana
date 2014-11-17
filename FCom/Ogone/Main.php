<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Ogone_Main
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 */

class FCom_Ogone_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main
            ->addPaymentMethod('ogone', 'FCom_Ogone_PaymentMethod')
            ->addCheckoutMethod('ogone', 'FCom_Ogone_CheckoutMethod')
        ;
    }
}
