<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PaymentBasic_Main
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 */
class FCom_PaymentBasic_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main->addPaymentMethod('basic', 'FCom_PaymentBasic_PaymentMethod');
    }
}
