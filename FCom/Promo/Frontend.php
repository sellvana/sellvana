<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Promo_Frontend extends BClass
{
    public function layout()
    {
        $this->BLayout->layout([
            '/promo/media' => [
                ['hook', 'main', 'views' => ['promo/media']]
            ],
        ]);
    }

    public function onPromoCartValidate($args)
    {
        /* @var $cart FCom_Sales_Model_Cart */
        $cart = $args['model'];

        $items = $cart->items();
        if (!$items) {
            $allCartPromo = $this->FCom_Promo_Model_Cart->orm()->where('cart_id', $cart->id)->find_many();
            foreach ($allCartPromo as $cartPromo) {
                $cartPromo->delete();
            }
            return;
        }

        $productIds = [];
        foreach ($items as $item) {
            /* @var $item FCom_Sales_Model_Cart_Item */
            if ($item->promo_id_get) {
                continue;
            }
            $item->promo_qty_used = 0;
            $item->promo_amt_used = 0;
            $item->promo_id_buy = '';
            $item->save();
            $productIds[$item->product_id] = $item;
        }
        if (!$productIds) {
            return;
        }

        $activePromo = [];
        $activePromoIds = [];
        $promoList = $this->FCom_Promo_Model_Promo->getActive();
        if (!$promoList) {
            return;
        }

        foreach ($promoList as $promo) {
            $promoProductsInGroup = $this->FCom_Promo_Model_Product->orm('p')
                    ->select('p.product_id')
                    ->select('p.group_id')
                    ->join($this->FCom_Promo_Model_Group->table(), "g.id = p.group_id", "g")
                    ->where('p.promo_id', $promo->id)
                    ->where_in('p.product_id', array_keys($productIds))
                    ->where('g.group_type', 'buy')
                    ->find_many();

            if (!$promoProductsInGroup) {
                continue;
            }

            //BUY qty
            if ('qty' == $promo->buy_type) {
                //FROM Single group
                if ('one' == $promo->buy_group) {
                    $groupProducts = [];
                    $groupQty = [];
                    foreach ($promoProductsInGroup as $product) {
                        if (!isset($groupProducts[$product->group_id])) {
                            $groupProducts[$product->group_id] = [];
                             $groupQty[$product->group_id] = 0;
                        }
                        if (!empty($productIds[$product->product_id])) {
                            $groupProducts[$product->group_id][] = $productIds[$product->product_id];
                            $groupQty[$product->group_id] += ($productIds[$product->product_id]->qty
                                - $productIds[$product->product_id]->promo_qty_used);
                        }
                        if ($promo->buy_amount <= $groupQty[$product->group_id]) {
                            //save how many
                            $activePromo[] = $promo;
                            $activePromoIds[] = $promo->id;
                            $promoBuyAmount = $promo->buy_amount;
                            foreach ($groupProducts[$product->group_id] as $groupItem) {
                                if (!empty($groupItem->promo_id_buy)) {
                                    $promoIds = explode(",", $groupItem->promo_id_buy);
                                    if (!in_array($promo->id, $promoIds)) {
                                        $promoIds[] = $promo->id;
                                    }
                                    $groupItem->promo_id_buy = implode(",", $promoIds);
                                } else {
                                    $groupItem->promo_id_buy = $promo->id;
                                }
                                if ($promoBuyAmount > 0) {
                                    $qtyUsed = $groupItem->qty - $promoBuyAmount;
                                    if ($qtyUsed <= 0) {
                                        $groupItem->promo_qty_used += $groupItem->qty;
                                    } else {
                                        $groupItem->promo_qty_used += $promoBuyAmount;
                                    }
                                    $promoBuyAmount -= $groupItem->qty;
                                }
                                $groupItem->save();
                            }

                            //only one promo per cart available
                            //break 2;
                        }
                    }
                }
                //FROM Any Group
                if ('any' == $promo->buy_group) {
                    $groupItems = [];
                    $productQty = 0;
                    foreach ($promoProductsInGroup as $product) {
                        if (!empty($productIds[$product->product_id])) {
                            $groupItems[] = $productIds[$product->product_id];
                            $productQty += ($productIds[$product->product_id]->qty
                                - $productIds[$product->product_id]->promo_qty_used);
                        }
                        if ($promo->buy_amount <= $productQty) {
                            $activePromo[] = $promo;
                            $activePromoIds[] = $promo->id;
                            $promoBuyAmount = $promo->buy_amount;
                            foreach ($groupItems as $groupItem) {
                                if (!empty($groupItem->promo_id_buy)) {
                                    $promoIds = explode(",", $groupItem->promo_id_buy);
                                    if (!in_array($promo->id, $promoIds)) {
                                        $promoIds[] = $promo->id;
                                    }
                                    $groupItem->promo_id_buy = implode(",", $promoIds);
                                } else {
                                    $groupItem->promo_id_buy = $promo->id;
                                }
                                if ($promoBuyAmount > 0) {
                                    $qtyUsed = $groupItem->qty - $promoBuyAmount;
                                    if ($qtyUsed <= 0) {
                                        $groupItem->promo_qty_used = $groupItem->qty;
                                    } else {
                                        $groupItem->promo_qty_used = $promoBuyAmount;
                                    }
                                    $promoBuyAmount -= $groupItem->qty;
                                }
                                $groupItem->save();
                            }
                            //only one promo per cart available
                            //break 2;
                        }
                    }
                }
            }
            if ('$' == $promo->buy_type) {
                if ('one' == $promo->buy_group) {
                    $groupProducts = [];
                    $groupQty = [];

                    foreach ($promoProductsInGroup as $product) {
                        if (!isset($groupProducts[$product->group_id])) {
                            $groupProducts[$product->group_id] = [];
                            $groupAmt[$product->group_id] = 0;
                        }
                        //TODO: validate that the logic is correct (!empty)
                        if (!empty($productIds[$product->product_id])) {
                            $groupProducts[$product->group_id][] = $productIds[$product->product_id];
                            $groupAmt[$product->group_id] += ($productIds[$product->product_id]->qty * $productIds[$product->product_id]->price
                                - $productIds[$product->product_id]->promo_amt_used);
                        }
                        if ($promo->buy_amount <= $groupAmt[$product->group_id]) {
                            $activePromo[] = $promo;
                            $activePromoIds[] = $promo->id;
                            $promoBuyAmount = $promo->buy_amount;
                            foreach ($groupProducts[$product->group_id] as $groupItem) {
                                if (!empty($groupItem->promo_id_buy)) {
                                    $promoIds = explode(",", $groupItem->promo_id_buy);
                                    if (!in_array($promo->id, $promoIds)) {
                                        $promoIds[] = $promo->id;
                                    }
                                    $groupItem->promo_id_buy = implode(",", $promoIds);
                                } else {
                                    $groupItem->promo_id_buy = $promo->id;
                                }
                                if ($promoBuyAmount > 0) {
                                    $amtUsed = $groupItem->qty * $groupItem->price - $promoBuyAmount;
                                    if ($amtUsed <= 0) {
                                        $groupItem->promo_amt_used = $groupItem->qty * $groupItem->price;
                                    } else {
                                        $groupItem->promo_amt_used = $promoBuyAmount;
                                    }
                                    $promoBuyAmount -= $groupItem->qty * $groupItem->price;
                                }
                                $groupItem->save();
                            }
                        }
                    }
                }
                if ('any' == $promo->buy_group) {
                    $productPrice = 0;
                    $groupItems = [];
                    foreach ($promoProductsInGroup as $product) {
                        if (!empty($productIds[$product->product_id])) {
                            $groupItems[] = $productIds[$product->product_id];
                            $productPrice += $productIds[$product->product_id]->price * $productIds[$product->product_id]->qty
                                - $productIds[$product->product_id]->promo_amt_used;
                        }
                    }

                    if ($promo->buy_amount <= $productPrice) {
                        $activePromo[] = $promo;
                        $activePromoIds[] = $promo->id;

                        $promoBuyAmount = $promo->buy_amount;
                        foreach ($groupItems as $groupItem) {
                            if (!empty($groupItem->promo_id_buy)) {
                                $promoIds = explode(",", $groupItem->promo_id_buy);
                                if (!in_array($promo->id, $promoIds)) {
                                    $promoIds[] = $promo->id;
                                }
                                $groupItem->promo_id_buy = implode(",", $promoIds);
                            } else {
                                $groupItem->promo_id_buy = $promo->id;
                            }
                            if ($promoBuyAmount > 0) {
                                $amtUsed = $groupItem->qty * $groupItem->price - $promoBuyAmount;
                                if ($amtUsed <= 0) {
                                    $groupItem->promo_amt_used = $groupItem->qty * $groupItem->price;
                                } else {
                                    $groupItem->promo_amt_used = $promoBuyAmount;
                                }
                                $promoBuyAmount -= $groupItem->qty * $groupItem->price;
                            }
                            $groupItem->save();
                        }
                    }
                }
            }
        }

        //check cart promo items
        $allCartItemPromo = $this->FCom_Sales_Model_Cart_Item->orm()->where('cart_id', $cart->id)
            ->where_not_equal('promo_id_get', 0)->find_many();
        foreach ($allCartItemPromo as $promoItem) {
            if (!in_array($promoItem->promo_id_get, $activePromoIds)) {
                $promoItem->delete();
            }
        }

        //check cart promos
        $allCartPromo = $this->FCom_Promo_Model_Cart->orm()->where('cart_id', $cart->id)->find_many();
        foreach ($allCartPromo as $cartPromo) {
            if (!in_array($cartPromo->promo_id, $activePromoIds)  || time() > strtotime($cartPromo->update_at) + 3600) {
                $cartPromo->delete();
            }
        }
        if (!empty($activePromo)) {
            foreach ($activePromo as $promo) {
                $promoCart = $this->FCom_Promo_Model_Cart->orm()->where('cart_id', $cart->id)
                        ->where('promo_id', $promo->id)
                    ->find_one();
                if (!$promoCart) {
                    $promoCart = $this->FCom_Promo_Model_Cart->create(['cart_id' => $cart->id, 'promo_id' => $promo->id]);
                }
                $promoCart->set('update_at', date("Y-m-d H:i:s"));
                $promoCart->save();
            }
        }
    }

    public function onPromoCartAddProduct($args)
    {
        /* @var $cart FCom_Sales_Model_Cart */
        $cart = $args['model'];
        /* @var $currentItem FCom_Sales_Model_Cart_Item */
        $currentItem = $args['item'];
        if ($currentItem->promo_id_get) {
            return;
        }

        $items = $cart->items();
        if (!$items) {
            return;
        }

        $promoList = [];
        foreach ($items as $item) {
            if (!$item->promo_id_buy) {
                continue;
            }
            if ($item->qty - $item->promo_qty_used == 0) {
                continue;
            }
            if ($item->price * $item->qty - $item->promo_amt_used < $item->price) {
                continue;
            }
            $promoIds = explode(",", $item->promo_id_buy);
            foreach ($promoIds as $promoId) {
                $promoList[$promoId] = $this->FCom_Promo_Model_Promo->load($promoId);
            }
        }

        if (!$promoList) {
            return;
        }

        foreach ($promoList as $promo) {
            //GET QTY
            if ($promo->get_type == 'qty') {
                //FROM Any Group
                if ($promo->get_group == 'any_group') {
                    $promoItemQtyTotal = 0;
                    foreach ($items as $item) {
                        if ($item->promo_id_get == $promo->id) {
                            $promoItemQtyTotal += $item->qty;
                        }
                    }

                    $item = $this->FCom_Sales_Model_Cart_Item->loadWhere([
                        'cart_id'      => $cart->id,
                        'product_id'   => $currentItem->product_id,
                        'promo_id_get' => $promo->id
                    ]);

                    //IF GET QTY < Item Qty then add 1
                    if ($item && $promo->get_amount > $item->qty) {
                        $item->qty += 1;
                    } elseif (!$item) {
                        //if it is single item of product then mark it as promo
                        if ($currentItem->qty == 1) {
                            $item = $currentItem;
                            $item->promo_id_get = $promo->id;
                            $item->promo_id_buy = '';
                            $item->price = 0;
                        } else {
                            //if not then add new promo item and decrase qty of current item
                            $item = $this->FCom_Sales_Model_Cart_Item->create([
                                'cart_id'      => $cart->id,
                                'product_id'   => $currentItem->product_id,
                                'qty'          => 1,
                                'price'        => 0,
                                'promo_id_get' => $promo->id
                            ]);

                            $currentItem->qty -= 1;
                            $currentItem->save();
                        }
                    } else {
                        continue;
                    }
                    $item->save();
                }
                //FROM Same Group
                if ($promo->get_group == 'same_group') {

                    $promoItemQtyTotal = 0;
                    foreach ($items as $item) {
                        if ($item->promo_id_get == $promo->id) {
                            $promoItemQtyTotal += $item->qty;
                        }
                    }

                    $productId = $currentItem->product_id;

                    $groupProduct = $this->FCom_Promo_Model_Product->orm()->where('promo_id', $promo->id())
                            ->where('product_id', $productId)->find_one();
                    if (!$groupProduct) {
                        continue;
                    }
                    $sameGroup = false;
                    foreach ($items as $item) {
                        if ($item->promo_id_get) {
                            continue;
                        }
                        $groupProductItem = $this->FCom_Promo_Model_Product->orm()->where('promo_id', $promo->id())
                            ->where('product_id', $item->product_id)
                            ->where('group_id', $groupProduct->group_id)->find_one();
                        if ($groupProductItem) {
                            $sameGroup = true;
                            break;
                        }
                    }
                    if ($sameGroup) {
                        /* @var $item FCom_Sales_Model_Cart_Item */
                         $item = $this->FCom_Sales_Model_Cart_Item->loadWhere([
                             'cart_id'      => $cart->id,
                             'product_id'   => $currentItem->product_id,
                             'promo_id_get' => $promo->id
                         ]);

                        //IF GET QTY < Item Qty then add 1
                        if ($item && $promo->get_amount > $item->qty) {
                            $item->qty += 1;
                        } elseif (!$item) {
                            //if it is single item of product then mark it as promo
                            if ($currentItem->qty == 1) {
                                $item = $currentItem;
                                $item->promo_id_get = $promo->id;
                                $item->promo_id_buy = '';
                                $item->price = 0;
                            } else {
                                //if not then add new promo item and decrease qty of current item
                                $item = $this->FCom_Sales_Model_Cart_Item->create([
                                    'cart_id'      => $cart->id,
                                    'product_id'   => $currentItem->product_id,
                                    'qty'          => 1,
                                    'price'        => 0,
                                    'promo_id_get' => $promo->id
                                ]);

                                $currentItem->qty -= 1;
                                $currentItem->save();
                            }
                        } else {
                            continue;
                        }
                        $item->save();
                    }

                }
                if ($promo->get_group == 'same_prod') {
                    $promoItemQtyTotal = 0;
                    foreach ($items as $item) {
                        if ($item->promo_id_get == $promo->id) {
                            $promoItemQtyTotal += $item->qty;
                        }
                    }

                    if ($currentItem->qty > 1 && $promo->get_amount > $promoItemQtyTotal) {
                         $item = $this->FCom_Sales_Model_Cart_Item->loadWhere([
                             'cart_id'      => $cart->id,
                             'product_id'   => $currentItem->product_id,
                             'promo_id_get' => $promo->id
                         ]);

                        //IF GET QTY < Item Qty then add 1
                        if ($item) {
                            $item->qty += 1;
                        } elseif (!$item) {
                            //if it is single item of product then mark it as promo
                            if ($currentItem->qty == 1) {
                                $item = $currentItem;
                                $item->promo_id_get = $promo->id;
                                $item->promo_id_buy = '';
                                $item->price = 0;
                            } else {
                                //file_put_contents("/tmp/data",print_r($currentItem,1));exit;
                                //if not then add new promo item and decrase qty of current item
                                $item = $this->FCom_Sales_Model_Cart_Item->create([
                                    'cart_id'      => $cart->id,
                                    'product_id'   => $currentItem->product_id,
                                    'qty'          => 1,
                                    'price'        => 0,
                                    'promo_id_get' => $promo->id
                                ]);

                                $currentItem->qty -= 1;
                                $currentItem->save();
                            }
                        } else {
                            continue;
                        }
                        $item->save();
                    }
                }

                //from different group
                if ('diff_group' == $promo->get_group) {
                    $groupIds = [];
                    foreach ($items as $item) {

                        $groupProductsBuy = $this->FCom_Promo_Model_Product->orm('p')
                            ->select('p.group_id')
                            ->join($this->FCom_Promo_Model_Group->table(), "g.id = p.group_id", "g")
                            ->where('p.promo_id', $promo->id())
                            ->where('p.product_id', $item->product_id)
                            ->where('g.group_type', 'buy')
                            ->find_many();
                        if ($groupProductsBuy) {
                            foreach ($groupProductsBuy as $gp) {
                                $groupIds[$gp->group_id] = $gp->group_id;
                            }
                        }
                    }

                    $groupProductGet = $this->FCom_Promo_Model_Product->orm('p')
                        ->select('p.group_id')
                        ->join($this->FCom_Promo_Model_Group->table(), "g.id = p.group_id", "g")
                        ->where('p.promo_id', $promo->id())
                        ->where('p.product_id', $currentItem->product_id)
                        ->where_not_in("p.group_id", $groupIds)
                        ->where('g.group_type', 'get')
                        ->find_many();

                    if ($groupProductGet) {
                        $item = $this->FCom_Sales_Model_Cart_Item->loadWhere([
                                'cart_id'      => $cart->id,
                                'product_id'   => $currentItem->product_id,
                                'promo_id_get' => $promo->id]);

                        //IF GET QTY < Item Qty then add 1
                        if ($item && $promo->get_amount > $item->qty) {
                            $item->qty += 1;
                        } elseif (!$item) {
                            //if it is single item of product then mark it as promo
                            if ($currentItem->qty == 1) {
                                $item = $currentItem;
                                $item->promo_id_get = $promo->id;
                                $item->promo_id_buy = '';
                                $item->price = 0;
                            } else {
                                //if not then add new promo item and decrase qty of current item
                                $item = $this->FCom_Sales_Model_Cart_Item->create([
                                    'cart_id'      => $cart->id,
                                    'product_id'   => $currentItem->product_id,
                                    'qty'          => 1,
                                    'price'        => 0,
                                    'promo_id_get' => $promo->id
                                ]);

                                $currentItem->qty -= 1;
                                $currentItem->save();
                            }
                        } else {
                            continue;
                        }
                        $item->save();
                    }
                }
            }

            //GET AMT
            if ($promo->get_type == '$') {
                //FROM Any Group
                if ($promo->get_group == 'any_group') {
                    $promoItemAmtTotal = 0;
                    foreach ($items as $item) {
                        if ($item->promo_id_get == $promo->id) {
                            $promoItemAmtTotal += $item->qty * $item->price;
                        }
                        //IF GET AMT < Item AMT total then accept discount
                        if ($promo->get_amount > $promoItemAmtTotal) {
                            //add discount for $promo->get_amt
                            //$cart->
                            break;

                        }
                    }
                }
            }
        }
    }

}
