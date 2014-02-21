<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_AuthorizeNet_Main extends BClass
{
    public static function bootstrap()
    {
        if (BConfig::i()->get('modules/FCom_AuthorizeNet/aim/active')) {
            FCom_Sales_Main::i()->addPaymentMethod('authnetaim', 'FCom_AuthorizeNet_PaymentMethod_Aim');
        }
        if (BConfig::i()->get('modules/FCom_AuthorizeNet/dpm/active')) {
            FCom_Sales_Main::i()->addPaymentMethod('authnetdpm', 'FCom_AuthorizeNet_PaymentMethod_Dpm');
        }
    }
}
