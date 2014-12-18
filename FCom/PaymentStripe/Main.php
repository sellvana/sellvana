<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PaymentStripe_Main
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 */
class FCom_PaymentStripe_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main
            ->addPaymentMethod('stripe', 'FCom_PaymentStripe_PaymentMethod')
        ;
    }
}