<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_PaymentStripe_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_PaymentStripe_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main
            ->addPaymentMethod('stripe', 'Sellvana_PaymentStripe_PaymentMethod')
        ;
    }
}