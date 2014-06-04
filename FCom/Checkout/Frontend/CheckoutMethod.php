<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Checkout_Frontend_CheckoutMethod extends FCom_Sales_Method_Checkout_Abstract
{
    public function getCartCheckoutButton()
    {
        return [
            'href'  => $this->BApp->href($this->FCom_Customer_Model_Customer->isLoggedIn() ? 'checkout' : 'checkout/login'),
            'label' => 'Proceed to Checkout',
        ];
    }
}
