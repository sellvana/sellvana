<?php

class FCom_Promo_Frontend extends BClass
{
    static public function bootstrap()
    {
        //add product to cart
        BPubSub::i()->on('FCom_Checkout_Model_Cart::addProduct',
                'FCom_Promo_Frontend::onAddToCart');

        BPubSub::i()->on('FCom_Checkout_Model_Cart::removeProduct',
                'FCom_Promo_Frontend::onRemoveFromCart');



    }

    public static function onAddToCart($args)
    {
        $cart = $args['model'];

        $items = $cart->items();

        $productIds = array();
        foreach($items as $item) {
            $productIds[$item->product_id] = $item;
        }

        $activePromo = array();
        $promoList = FCom_Promo_Model_Promo::i()->getActive();
        foreach($promoList as $promo) {
            $promoProductsInGroup = FCom_Promo_Model_Product::orm()
                            ->where('promo_id', $promo->id)
                            ->where_in('product_id', array_keys($productIds))
                            ->find_many();

            if (empty($promoProductsInGroup)) {
                continue;
            }

            if ('qty' == $promo->buy_type) {
                if ('one' == $promo->buy_group) {
                    $groupProducts = array();
                    foreach($promoProductsInGroup as $product) {
                        if (!isset($groupProducts[$product->group_id])) {
                            $groupProducts[$product->group_id] = 0;
                        }
                        if (!empty($productIds[$product->product_id])) {
                            $groupProducts[$product->group_id] += $productIds[$product->product_id]->qty;
                        }
                    }
                    foreach ($groupProducts as $productQty) {
                        if ($promo->buy_amount <= $productQty ) {
                            $activePromo[] = $promo;
                        }
                    }
                }
                if ('all' == $promo->buy_group) {
                    $productQty = 0;
                    foreach($promoProductsInGroup as $product) {
                        if (!empty($productIds[$product->product_id])) {
                            $productQty += $productIds[$product->product_id]->qty;
                        }
                    }
                    if ($promo->buy_amount <= $productQty ) {
                        $activePromo[] = $promo;
                    }
                }
            }
            if ('$' == $promo->buy_type) {
                if ('one' == $promo->buy_group) {
                    $groupProducts = array();
                    foreach($promoProductsInGroup as $product) {
                        if (!isset($groupProducts[$product->group_id])) {
                            $groupProducts[$product->group_id] = 0;
                        }
                        if (!empty($productIds[$product->product_id])) {
                            $groupProducts[$product->group_id] += $productIds[$product->product_id]->price;
                        }
                    }
                    foreach ($groupProducts as $productPrice) {
                        if ($promo->buy_amount <= $productPrice ) {
                            $activePromo[] = $promo;
                        }
                    }
                }
                if ('all' == $promo->buy_group) {
                    $productPrice = 0;
                    foreach($promoProductsInGroup as $product) {
                        if (!empty($productIds[$product->product_id])) {
                            $productPrice += $productIds[$product->product_id]->price;
                        }
                    }
                    if ($promo->buy_amount <= $productPrice ) {
                        $activePromo[] = $promo;
                    }
                }
            }
        }

        if (!empty($activePromo)) {
            foreach($activePromo as $promo) {
                $promoCart = FCom_Promo_Model_Cart::orm()->where('cart_id', $cart->id)
                        ->where('promo_id', $promo->id)
                    ->find_one();
                if (!$promoCart) {
                    $promoCart = FCom_Promo_Model_Cart::create(array('cart_id'=>$cart->id, 'promo_id'=>$promo->id));
                }
                $promoCart->set('updated_dt', date("Y-m-d H:i:s"));
                $promoCart->save();
            }
        }

        /**
         * todo: validate promo cache table
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

    public static function onRemoveFromCart($args)
    {
        $this->onAddToCart($args);
    }
}
