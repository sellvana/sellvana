<?php

class FCom_Promo_Frontend extends BClass
{
    static public function bootstrap()
    {
        //add product to cart
        BPubSub::i()->on('FCom_Checkout_Model_Cart::calcTotals',
                'FCom_Promo_Frontend::onPromoCartValidate');
        BPubSub::i()->on('FCom_Checkout_Model_Cart::addProduct', 'FCom_Promo_Frontend::onPromoCartAddProduct');

        BPubSub::i()
            ->on('BLayout::hook.promotions', 'FCom_Promo_Frontend_Controller.hook_promotions')
        ;

        BFrontController::i()
            ->route( 'GET /promo/media', 'FCom_Promo_Frontend_Controller.media')
        ;

        BLayout::i()->addAllViews('Frontend/views');
        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_Promo_Frontend::layout');

    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            '/promo/media'=>array(
                array('hook', 'main', 'views'=>array('promo/media'))
            ),
        ));
    }

    public static function onPromoCartValidate($args)
    {
        $cart = $args['model'];

        $items = $cart->items();
        if (!$items) {
            $allCartPromo = FCom_Promo_Model_Cart::orm()->where('cart_id', $cart->id)->find_many();
            foreach($allCartPromo as $cartPromo) {
                $cartPromo->delete();
            }
            return;
        }

        $productIds = array();
        foreach($items as $item) {
            $productIds[$item->product_id] = $item;
        }

        $activePromo = array();
        $activePromoIds = array();
        $promoList = FCom_Promo_Model_Promo::i()->getActive();
        if (!$promoList) {
            return;
        }
        foreach($promoList as $promo) {
            $promoProductsInGroup = FCom_Promo_Model_Product::orm()
                            ->where('promo_id', $promo->id)
                            ->where_in('product_id', array_keys($productIds))
                            ->find_many();

            if (!$promoProductsInGroup) {
                continue;
            }

            //BUY qty
            if ('qty' == $promo->buy_type) {
                //FROM Single group
                if ('one' == $promo->buy_group) {
                    $groupProducts = array();
                    $groupQty = array();
                    foreach($promoProductsInGroup as $product) {
                        if (!isset($groupProducts[$product->group_id])) {
                            $groupProducts[$product->group_id] = array();
                             $groupQty[$product->group_id] = 0;
                        }
                        if (!empty($productIds[$product->product_id])) {
                            $groupProducts[$product->group_id][] = $productIds[$product->product_id];
                            $groupQty[$product->group_id] += $productIds[$product->product_id]->qty;
                        }
                        if ($promo->buy_amount <= $groupQty[$product->group_id] ) {
                            $activePromo[] = $promo;
                            $activePromoIds[] = $promo->id;
                            foreach($groupProducts[$product->group_id] as $groupItem) {
                                $groupItem->promo_id_buy = $promo->id;
                                $groupItem->save();
                            }
                            //only one promo per cart available
                            break 2;
                        }
                    }
                }
                //FROM All Group
                if ('all' == $promo->buy_group) {
                    $groupItems = array();
                    $productQty = 0;
                    foreach($promoProductsInGroup as $product) {
                        if (!empty($productIds[$product->product_id])) {
                            $groupItems[] = $productIds[$product->product_id];
                            $productQty += $productIds[$product->product_id]->qty;
                        }
                        if ($promo->buy_amount <= $productQty ) {
                            $activePromo[] = $promo;
                            $activePromoIds[] = $promo->id;
                            foreach($groupItems as $groupItem) {
                                $groupItem->promo_id_buy = $promo->id;
                                $groupItem->save();
                            }
                            //only one promo per cart available
                            break 2;
                        }
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
                            $groupProducts[$product->group_id] += $productIds[$product->product_id]->price*$productIds[$product->product_id]->qty;
                        }
                    }
                    foreach ($groupProducts as $productPrice) {
                        if ($promo->buy_amount <= $productPrice ) {
                            $activePromo[] = $promo;
                            $activePromoIds[] = $promo->id;
                        }
                    }
                }
                if ('all' == $promo->buy_group) {
                    $productPrice = 0;
                    foreach($promoProductsInGroup as $product) {
                        if (!empty($productIds[$product->product_id])) {
                            $productPrice += $productIds[$product->product_id]->price*$productIds[$product->product_id]->qty;
                        }
                    }

                    if ($promo->buy_amount <= $productPrice ) {
                        $activePromo[] = $promo;
                        $activePromoIds[] = $promo->id;
                    }
                }
            }
        }

        $allCartPromo = FCom_Promo_Model_Cart::orm()->where('cart_id', $cart->id)->find_many();
        foreach($allCartPromo as $cartPromo) {
            if (!in_array($cartPromo->promo_id, $activePromoIds)  || time() > strtotime($cartPromo->updated_dt) + 3600) {
                $cartPromo->delete();
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

    public static function onPromoCartAddProduct($args)
    {
        $cart = $args['model'];
        $currentItem = $args['item'];
        if ($currentItem->promo_id_get) {
            return;
        }

        $items = $cart->items();
        if (!$items) {
            return;
        }

        $promo = false;
        foreach($items as $item) {
            if (!$item->promo_id_buy) {
                continue;
            }
            $promo = FCom_Promo_Model_Promo::load($item->promo_id_buy);
        }

        if (!$promo) {
            return;
        }

        if ($promo->get_type == 'qty') {
            if ($promo->get_group == 'any_group') {
                $itemQtyTotal = 0;
                $promoItemQtyTotal = 0;
                foreach($items as $item) {
                    $itemQtyTotal += $item->qty;
                    if ($item->promo_id_get) {
                        $promoItemQtyTotal += $item->qty;
                    }
                }
                $itemQtyLeft = $itemQtyTotal - $promo->buy_amount;

                if ($itemQtyLeft > 0) {

                    $item = FCom_Checkout_Model_CartItem::load(array('cart_id'=>$cart->id, 'product_id'=>$currentItem->product_id, 'promo_id_get' => $promo->id));

                    if ($item && $promo->get_amount > $promoItemQtyTotal) {
                        $item->qty += 1;
                    } elseif (!$item) {
                        if ($currentItem->qty == 1) {
                            $item = $currentItem;
                            $item->promo_id_get = $promo->id;
                            $item->price = 0;
                        } else {
                            $item = FCom_Checkout_Model_CartItem::create(array('cart_id'=>$cart->id, 'product_id'=>$currentItem->product_id,
                                'qty'=>1, 'price' => 0, 'promo_id_get' => $promo->id));

                            $currentItem->qty -= 1;
                            $currentItem->save();
                        }
                    } else {
                        return;
                    }
                    $item->save();



                }
            }
        }
    }

}
