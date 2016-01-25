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
        $result = '';

        if ($this->_validateSettings()) {
            $result = [
                'html' => $this->BLayout->view('ogone/checkout-button')
                    ->set($this->Sellvana_PaymentOgone_RemoteApi->prepareRequestData())->render(),
            ];
        }

        return $result;
    }

    protected function _validateSettings()
    {
        $config = $this->BConfig->get('modules/Sellvana_PaymentOgone');
        if (is_null($config)) {
            return false;
        }

        if (empty($config['pspid']) || empty($config['owner_address'] || empty($config['owner_country']))) {
            return false;
        }

        $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();
        if ($cart && $cart->hasUnavailableItems()) {
            return false;
        }

        return true;
    }
}
