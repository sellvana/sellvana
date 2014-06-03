<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class FCom_PaymentIdeal_Main extends BClass
{
    public function bootstrap()
    {
        if ($this->BConfig->get('modules/FCom_PaymentIdeal/active')) {
            $this->FCom_Sales_Main->addPaymentMethod('ideal', 'FCom_PaymentIdeal_PaymentMethod');
        }
    }
}
