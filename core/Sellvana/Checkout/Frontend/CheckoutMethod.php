<?php

/**
 * Class Sellvana_Checkout_Frontend_CheckoutMethod
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */

class Sellvana_Checkout_Frontend_CheckoutMethod extends Sellvana_Sales_Method_Checkout_Abstract
{
    public function getCartCheckoutButton()
    {
        $result = [
            'href'  => $this->BApp->href('checkout'),
            'label' => 'Proceed to Checkout',
        ];
        $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();
        if ($cart && $cart->hasUnavailableItems()) {
            $result['disabled'] = true;
            $result['label'] = 'Please remove unavailable items';
        }
        return $result;
    }
}
