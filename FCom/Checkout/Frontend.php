<?php

class FCom_Checkout_Frontend extends BClass
{
    static public function bootstrap()
    {
        FCom_Sales_Main::i()->addCheckoutMethod('default', 'FCom_Checkout_Frontend_CheckoutMethod');
    }

    /**
     * Init cart after all modules are registered
     */
    public static function initCartTotals()
    {
        $cart = FCom_Checkout_Model_Cart::sessionCart();
        if (false == $cart->items()) {
            return;
        }
        FCom_Checkout_Model_Cart::i()->addTotalRow('subtotal', array('callback'=>'FCom_Checkout_Model_Cart.subtotalCallback', 'label' => 'Subtotal', 'after'=>''));
        if ($cart->shipping_method) {
            $shippingClass = FCom_Sales_Main::i()->getShippingMethodClassName($cart->shipping_method);
            FCom_Checkout_Model_Cart::i()->addTotalRow('shipping', array('callback'=>$shippingClass.'.getRateCallback',
                'label' => 'Shipping', 'after'=>'subtotal'));
        }
        if ($cart->discount_code) {
            FCom_Checkout_Model_Cart::i()->addTotalRow('discount', array('callback'=>'FCom_Checkout_Model_Cart.discountCallback',
                'label' => 'Discount', 'after'=>'shipping'));
        }
    }
}

