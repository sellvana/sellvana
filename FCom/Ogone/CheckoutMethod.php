<?php

class FCom_Ogone_CheckoutMethod extends FCom_Sales_Method_Checkout_Abstract
{
    public function getCartCheckoutButton()
    {
        return array(
            'html' => BLayout::i()->view( 'ogone/checkout-button' )
                ->set( FCom_Ogone_RemoteApi::i()->prepareRequestData() )->render(),
        );
    }
}