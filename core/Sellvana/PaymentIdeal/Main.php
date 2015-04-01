<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_PaymentIdeal_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_PaymentIdeal_Main extends BClass
{
    public function bootstrap()
    {
        if ($this->BConfig->get('modules/Sellvana_PaymentIdeal/active')) {
            $this->Sellvana_Sales_Main->addPaymentMethod('ideal', 'Sellvana_PaymentIdeal_PaymentMethod');
        }
    }
}
