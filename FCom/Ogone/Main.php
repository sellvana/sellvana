<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
