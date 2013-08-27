<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_AuthorizeNet_Main extends BClass
{
    public static function bootstrap()
    {
        FCom_Sales_Main::i()->addPaymentMethod('authnetaim', 'FCom_AuthorizeNet_PaymentMethod_Aim');
        FCom_Sales_Main::i()->addPaymentMethod('authnetdpm', 'FCom_AuthorizeNet_PaymentMethod_Dpm');
//        die(__METHOD__);
    }
}
