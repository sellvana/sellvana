<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Ogone_CheckoutMethod extends FCom_Sales_Method_Checkout_Abstract
{
    public function getCartCheckoutButton()
    {
        return [
            'html' => BLayout::i()->view('ogone/checkout-button')
                ->set(FCom_Ogone_RemoteApi::i()->prepareRequestData())->render(),
        ];
    }
}