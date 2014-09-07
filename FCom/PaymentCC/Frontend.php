<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_PaymentCC_Frontend extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Sales_Main->addPaymentMethod('cc', 'FCom_PaymentCC_PaymentMethod');
    }
}
