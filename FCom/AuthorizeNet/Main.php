<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 * @property FCom_Sales_Main $FCom_Sales_Main
 */

class FCom_AuthorizeNet_Main extends BClass
{
    public function bootstrap()
    {
        if ($this->BConfig->get('modules/FCom_AuthorizeNet/aim/active')) {
            $this->FCom_Sales_Main->addPaymentMethod('authnetaim', 'FCom_AuthorizeNet_PaymentMethod_Aim');
        }
        if ($this->BConfig->get('modules/FCom_AuthorizeNet/dpm/active')) {
            $this->FCom_Sales_Main->addPaymentMethod('authnetdpm', 'FCom_AuthorizeNet_PaymentMethod_Dpm');
        }
    }
}
