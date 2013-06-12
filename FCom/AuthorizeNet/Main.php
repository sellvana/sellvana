<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_AuthorizeNet_Main extends BClass
{
    public static function bootstrap()
    {
        FCom_Sales_Main::i()->addPaymentMethod('authnet', 'FCom_AuthorizeNet_PaymentMethod');
//        die(__METHOD__);
    }
}
