<?php

class FCom_Checkout_Frontend extends BClass
{
    static public function bootstrap()
    {

        BRouting::i()
            //cart
            ->route('GET|POST /cart', 'FCom_Checkout_Frontend_Controller.cart')
            //checkout
            ->route('GET|POST /checkout', 'FCom_Checkout_Frontend_Controller_Checkout.checkout')
            //login
            ->route('GET /checkout/login', 'FCom_Checkout_Frontend_Controller_Checkout.checkout_login')
            //payment
            ->route('GET|POST /checkout/payment', 'FCom_Checkout_Frontend_Controller_Checkout.payment')
            //shipping
            ->route('GET|POST /checkout/shipping', 'FCom_Checkout_Frontend_Controller_Checkout.shipping')
            //checkout finish page
            ->route('GET /checkout/success', 'FCom_Checkout_Frontend_Controller_Checkout.success')
            //shipping address
            ->route('GET|POST /checkout/address', 'FCom_Checkout_Frontend_Controller_Address.address')
        ;

        //merge cart sessions after user login
        BPubSub::i()->on('FCom_Customer_Model_Customer::login.after', 'FCom_Checkout_Model_Cart::userLogin');
        BPubSub::i()->on('FCom_Customer_Model_Customer::logout.before', 'FCom_Checkout_Model_Cart::userLogout');

        //add to cart
        BPubSub::i()->on('FCom_Catalog_Frontend_Controller::action_product.addToCart',
                'FCom_Checkout_Frontend_Controller::onAddToCart');

        BPubSub::i()->on('bootstrap::after', 'FCom_Checkout_Frontend::initCartTotals');

        BLayout::i()->addAllViews('Frontend/views')
                ->afterTheme('FCom_Checkout_Frontend::layout');
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
            $shippingClass = FCom_Checkout_Model_Cart::i()->getShippingClassName($cart->shipping_method);
            FCom_Checkout_Model_Cart::i()->addTotalRow('shipping', array('callback'=>$shippingClass.'.getRateCallback',
                'label' => 'Shipping', 'after'=>'subtotal'));
        }
        if ($cart->discount_code) {
            FCom_Checkout_Model_Cart::i()->addTotalRow('discount', array('callback'=>'FCom_Checkout_Model_Cart.discountCallback',
                'label' => 'Discount', 'after'=>'shipping'));
        }
    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'head', 'do'=>array(
                    array('js', '{FCom_Checkout}/Frontend/js/fcom.checkout.js'),
                )
            )),
            '/checkout/cart'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/cart'))
            ),
            '/checkout/checkout'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/checkout'))
            ),
            '/checkout/login'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/login'))
            ),
            '/checkout/payment'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/payment'))
            ),
            '/checkout/shipping'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/shipping'))
            ),
            '/checkout/address'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/address'))
            ),
            '/checkout/success'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/success'))
            ),
        ));
    }

}

