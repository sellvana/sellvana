<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Ogone_CheckoutMethod extends FCom_Sales_Method_Checkout_Abstract
{
    public function getCartCheckoutButton()
    {
        return [
            'html' => $this->BLayout->view('ogone/checkout-button')
                ->set($this->FCom_Ogone_RemoteApi->prepareRequestData())->render(),
        ];
    }
}
