<?php

class FCom_Ogone_CheckoutMethod extends BClass implements FCom_Sales_Interface_CheckoutMethod
{
    public function getCartCheckoutButton()
    {
        return BLayout::i()->view('ogone/checkout-button')
            ->set(FCom_Ogone_RemoteApi::i()->prepareRequestData());
    }
}