<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_PaymentIdeal_Main extends BClass
{
    public static function bootstrap()
    {
        if(BConfig::i()->get('modules/FCom_PaymentIdeal/active')){
            FCom_Sales_Main::i()->addPaymentMethod('ideal', 'FCom_PaymentIdeal_PaymentMethod');
        }
    }
}
