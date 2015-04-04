<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_PaymentBasic_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_PaymentBasic_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addPaymentMethod('basic', 'Sellvana_PaymentBasic_PaymentMethod');
    }
}
