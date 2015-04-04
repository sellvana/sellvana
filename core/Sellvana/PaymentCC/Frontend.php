<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_PaymentCC_Frontend
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_PaymentCC_Frontend extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addPaymentMethod('cc', 'Sellvana_PaymentCC_PaymentMethod');
    }
}
