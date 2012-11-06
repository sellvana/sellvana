<?php

class FCom_Promo_Frontend extends BClass
{
    static public function bootstrap()
    {
        //add product to cart
        BPubSub::i()->on('FCom_Checkout_Model_Cart::addProduct',
                'FCom_Promo_Frontend::onAddToCart');

    }

    public static function onAddToCart($args)
    {
        $cart = $args['model'];

        $items = $cart->items();

        /**
         * todo:
         * 1. For each Promo
         * 2. Calculate promo formula like: BUY 	Quantity 	FROM 	Single Group 	GET 	Quantity 	OF 	Any Group
         *  Formula:
         *  IF number of products in the cart FROM Single Group > BUY Quantity
         *  THEN suggest Quantity of products OF Any Group
         * 3. Display suggestions in tooltip on 'Add product' button
         * 4. Display suggestions in the cart
         * 5. Save suggestions in special cache table
         * 6. Validate suggestions in the cache table each day
         *
         */

    }
}
