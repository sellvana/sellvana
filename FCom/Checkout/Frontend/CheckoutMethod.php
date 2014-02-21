<?php

class FCom_Checkout_Frontend_CheckoutMethod extends FCom_Sales_Method_Checkout_Abstract
{
    public function getCartCheckoutButton()
    {
        return array(
            'href'  => BApp::href(FCom_Customer_Model_Customer::i()->isLoggedIn() ? 'checkout' : 'checkout/login'),
            'label' => 'Proceed to Checkout',
        );
    }
}