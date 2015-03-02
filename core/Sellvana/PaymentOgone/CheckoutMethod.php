<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_PaymentOgone_CheckoutMethod
 *
 * @property Sellvana_PaymentOgone_RemoteApi $Sellvana_PaymentOgone_RemoteApi
 */

class Sellvana_PaymentOgone_CheckoutMethod extends Sellvana_Sales_Method_Checkout_Abstract
{
    public function getCartCheckoutButton()
    {
        return [
            'html' => $this->BLayout->view('ogone/checkout-button')
                ->set($this->Sellvana_PaymentOgone_RemoteApi->prepareRequestData())->render(),
        ];
    }
}
