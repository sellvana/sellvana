<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_PaymentOgone_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */

class Sellvana_PaymentOgone_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_Sales_Main
            ->addPaymentMethod('ogone', 'Sellvana_PaymentOgone_PaymentMethod')
            ->addCheckoutMethod('ogone', 'Sellvana_PaymentOgone_CheckoutMethod')
        ;
    }
}
