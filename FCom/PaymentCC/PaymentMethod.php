<?php

class FCom_PaymentCC_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    public function getName()
    {
        return 'Credit Card';
    }

    public function payOnCheckout()
    {
        die( "Credit Card payment not imlemented yet" );
    }

    public function getCheckoutFormView()
    {
        return BLayout::i()->view( 'credit_card/form' );
    }
}
