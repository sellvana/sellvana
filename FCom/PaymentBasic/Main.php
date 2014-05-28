<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_PaymentBasic_Main extends BClass
{
    static public function bootstrap()
    {
        FCom_Sales_Main::i()->addPaymentMethod('basic', 'FCom_PaymentBasic_PaymentMethod');
    }
}
