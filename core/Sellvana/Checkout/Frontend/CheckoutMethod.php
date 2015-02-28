<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Checkout_Frontend_CheckoutMethod
 *
 */

class Sellvana_Checkout_Frontend_CheckoutMethod extends Sellvana_Sales_Method_Checkout_Abstract
{
    public function getCartCheckoutButton()
    {
        return [
            'href'  => $this->BApp->href('checkout'),
            'label' => 'Proceed to Checkout',
        ];
    }
}
