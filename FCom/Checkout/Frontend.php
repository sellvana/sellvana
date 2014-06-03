<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        if (false == $cart->items()) {
            return;
        }
        FCom_Sales_Model_Cart::i()->addTotalRow('subtotal', ['callback' => 'FCom_Sales_Model_Cart.subtotalCallback',
            'label' => 'Subtotal', 'after' => '']);
        if ($cart->shipping_method) {
            $shippingClass = FCom_Sales_Main::i()->getShippingMethodClassName($cart->shipping_method);
            FCom_Sales_Model_Cart::i()->addTotalRow('shipping', ['callback' => $shippingClass . '.getRateCallback',
                'label' => 'Shipping', 'after' => 'subtotal']);
        }
        if ($cart->coupon_code) {
            FCom_Sales_Model_Cart::i()->addTotalRow('discount', ['callback' => 'FCom_Sales_Model_Cart.discountCallback',
                'label' => 'Discount', 'after' => 'shipping']);
        }
    }
}

