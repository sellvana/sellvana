<?php

class FCom_ShippingFree_Main extends BClass
{

    public static function bootstrap()
    {
        // only check cart if module is enabled
        if ( BConfig::i()->get( 'modules/FCom_ShippingFree/active' ) ) {
            // get cart and check for promotions which have get_type 'free'
            $cart = FCom_Sales_Model_Cart::i()->sessionCart();

            $promoCart = FCom_Promo_Model_Promo::orm( 'p' )
                                               ->where( 'p.get_type', FCom_ShippingPlain_ShippingMethod::FREE_SHIPPING )
                                               ->join( FCom_Promo_Model_Cart::table(), 'pc.promo_id=p.id', 'pc' )
                                               ->where('pc.cart_id', $cart->id)
                                               ->find_one();
            if ( $promoCart ) {
                FCom_Sales_Main::i()->addShippingMethod( 'free_shipping', 'FCom_ShippingFree_ShippingMethod' );
            }
        }
    }
}