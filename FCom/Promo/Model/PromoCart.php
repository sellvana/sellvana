<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Model_PromoCart
 *
 * @property int $id
 * @property int $cart_id
 * @property int $promo_id
 * @property string $update_at
 *
 * @property FCom_Promo_Main $FCom_Promo_Main
 * @property FCom_Promo_Model_Promo $FCom_Promo_Model_Promo
 * @property FCom_Promo_Model_PromoCartItem $FCom_Promo_Model_PromoCartItem
 * @property FCom_Catalog_Model_CategoryProduct $FCom_Catalog_Model_CategoryProduct
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 */
class FCom_Promo_Model_PromoCart extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_cart';

    public function validateConditions(FCom_Promo_Model_Promo $promo, FCom_Sales_Model_Cart $cart)
    {
        $matchType = $cart->getData('conditions/match') ?: FCom_Promo_Model_Promo::MATCH_ANY; //TODO: remove default after testing
        $result = [
            'match' => $matchType === FCom_Promo_Model_Promo::MATCH_ALL ? true : false,
            'items' => [],
        ];
        if ($promo->get('status') !== 'active' || $promo->get('promo_type') !== 'cart') {
            return $result;
        }
        $now = $this->BDb->now();
        if (!(
            ($this->BUtil->isEmptyDate($promo->get('from_date')) || $promo->get('from_date') < $now)
            && ($this->BUtil->isEmptyDate($promo->get('to_date')) || $promo->get('to_date') > $now)
        )) {
            return $result;
        }

        $conditionRules = $promo->getData('conditions/rules');
        if (!$conditionRules) {
            $result['match'] = true;
            $result['items'] = $cart->items();
            return $result;
        }

        foreach ($conditionRules as $conditionType => $conditions) {
            foreach ($conditions as $condition) {
                #$conditionType = !empty($condition['type']) ? $condition['type'] : 'skus';
                switch ($conditionType) {
                    case 'sku':
                        $match = $this->_validateSkus($cart, $condition, $result);
                        break;

                    case 'category':
                        $match = $this->_validateCategories($cart, $condition, $result);
                        break;

                    case 'total':
                        $match = $this->_validateTotal($cart, $condition, $result);
                        break;

                    case 'combination':
                        $match = $this->_validateCombination($cart, $condition, $result);
                        break;

                    case 'shipping':
                        $match = $this->_validateShipping($cart, $condition, $result);
                        break;

                    default:
                        throw new BException('Invalid condition type: ' . $conditionType);
                }
                if ($matchType === FCom_Promo_Model_Promo::MATCH_ANY && $match) {
                    $result['match'] = true;
                    #break; // don't stop loop to collect all matched products to use in actions
                } elseif ($matchType === FCom_Promo_Model_Promo::MATCH_ALL && !$match) {
                    $result['match'] = false;
                    #break;
                }
            }
        }
        return $result;
    }

    protected function _validateSkus(FCom_Sales_Model_Cart $cart, array $condition, array &$result)
    {
        if (empty($condition['sku'])) {
            return false;
        }
        $skus = array_flip((array)$condition['sku']);
        $total = 0;
        $items = [];
        /** @var FCom_Sales_Model_Cart_Item $item */
        foreach ($cart->items() as $item) {
            if ($item->get('auto_added')) {
                continue;
            }
            if (!isset($skus[$item->get('product_sku')])) {
                continue;
            }
            switch ($condition['type']) {
                case 'qty':
                    $total += $item->get('qty');
                    break;

                case 'amount':
                    $total += $item->get('row_total');
                    break;
            }
            $result['items'][$item->id()] = $item;
        }
        $conditionTotalValue = !empty($condition['value']) ? $condition['value'] : 0;
        if ($this->FCom_Promo_Main->compareValues($total, $conditionTotalValue, $condition['filter'])) {
            foreach ($items as $item) {
                $result['items'][$item->id()] = $item;
            }
            return true;
        } else {
            return false;
        }
    }

    protected function _validateCategories(FCom_Sales_Model_Cart $cart,
                                           array $condition, array &$result)
    {
        if (empty($condition['category_id'])) {
            return false;
        }
        $catIds = (array)$condition['category_id'];
        $prodIds = [];
        foreach ($cart->items() as $item) {
            $prodIds[] = $item->get('product_id');
        }
        $catProdLinks = $this->FCom_Catalog_Model_CategoryProduct->orm()
            ->where_in('category_id', $catIds)
            ->where_in('product_id', $prodIds)
            ->find_many_assoc('product_id');
        if (!$catProdLinks) {
            return $result;
        }
        $total = 0;
        $items = [];
        $foundProdIds = array_flip(array_keys($catProdLinks));
        /** @var FCom_Sales_Model_Cart_Item $item */
        foreach ($cart->items() as $item) {
            if ($item->get('auto_added')) {
                continue;
            }
            if (!isset($foundProdIds[$item->get('product_id')])) {
                continue;
            }
            switch ($condition['type']) {
                case 'qty':
                    $total += $item->get('qty');
                    break;

                case 'amount':
                    $total += $item->get('row_total');
                    break;
            }
            $items[$item->id()] = $item;
        }
        $conditionTotalValue = !empty($condition['value']) ? $condition['value'] : 0;
        if ($this->FCom_Promo_Main->compareValues($total, $conditionTotalValue, $condition['filter'])) {
            foreach ($items as $item) {
                $result['items'][$item->id()] = $item;
            }
            return true;
        } else {
            return false;
        }
    }

    protected function _validateTotal(FCom_Sales_Model_Cart $cart, array $condition, array &$result)
    {
        $conditionTotalValue = !empty($condition['value']) ? $condition['value'] : 0;
        if ($this->FCom_Promo_Main->compareValues($cart->get('subtotal'), $conditionTotalValue, $condition['filter'])) {
            foreach ($cart->items() as $item) {
                $result['items'][$item->id()] = $item;
            }
            return true;
        } else {
            return false;
        }
    }

    protected function _validateCombination(FCom_Sales_Model_Cart $cart, array $condition, array &$result)
    {
        if (empty($condition['fields'])) {
            return false;
        }

        $cart->loadProducts();
        $match = false;
        foreach ($cart->items() as $item) {
            if ($item->get('auto_added')) {
                continue;
            }
            $product = $item->getProduct();
            $variantFields = $item->getData('variant_fields');
            if ($variantFields) {
                $product->setReadOnly()->set($variantFields);
            }
            if ($this->FCom_Promo_Main->validateProductConditionCombination($product, $condition)) {
                $result['items'][$item->id()] = $item;
                $match = true;
            }
        }
        return $match;
    }

    protected function _validateShipping(FCom_Sales_Model_Cart $cart, array $condition, array &$result)
    {
        if (empty($condition['fields'])) {
            return false;
        }
        $hlp = $this->FCom_Promo_Main;
        $address = $cart->getShippingAddress();
        foreach ($condition['fields'] as $fieldCond) {
            switch ($fieldCond['field']) {
                case 'methods':
                    $res = $hlp->compareValues($cart->get('shipping_method'), $fieldCond['value'], $fieldCond['filter']);
                    break;

                case 'country':
                    $res = $hlp->compareValues($address->get('country'), $fieldCond['value'], $fieldCond['filter']);
                    break;

                case 'state': //TODO: 'region'
                    $res = $hlp->compareValues($address->get('region'), $fieldCond['value'], $fieldCond['filter']);
                    break;

                case 'zipcode': //TODO: 'postcode'
                    $res = $hlp->compareValues($address->get('postcode'), $fieldCond['value'], $fieldCond['filter']);
                    break;

                default:
                    throw new BException('Invalid condition field: ' . $fieldCond['field']);
            }
            if ($condition['match'] === FCom_Promo_Model_Promo::MATCH_ANY && $res) {
                return true;
            } elseif ($condition['match'] === FCom_Promo_Model_Promo::MATCH_ALL && !$res) {
                return false;
            }
        }
        return $condition['match'] === FCom_Promo_Model_Promo::MATCH_ALL ? false : true;
    }

    public function calculateActions(FCom_Promo_Model_Promo $promo, FCom_Sales_Model_Cart $cart,
                                     array $conditionsResult, array &$actionsResult)
    {
        if (!empty($conditionsResult['match']) && !empty($conditionsResult['items'])) {
            foreach ($conditionsResult['items'] as $itemId => $item) {
                $actionsResult['promos'][$promo->id()]['matched_items'][$itemId] = $itemId;
            }
        }

        $actions = $promo->getData('actions/rules/discount');
        if ($actions) {
            foreach ($actions as $action) {
                $this->_calcSubtotalDiscount($promo, $cart, $action, $conditionsResult, $actionsResult);
            }
        }

        $actions = $promo->getData('actions/rules/shipping');
        if ($actions) {
            foreach ($actions as $action) {
                $this->_calcShippingDiscount($promo, $cart, $action, $conditionsResult, $actionsResult);
            }
        }

        $actions = $promo->getData('actions/rules/free_product');
        if ($actions) {
            foreach ($actions as $action) {
                $this->_calcFreeProduct($promo, $cart, $action, $conditionsResult, $actionsResult);
            }
        }
        return $this;
    }

    protected function _calcSubtotalDiscount(FCom_Promo_Model_Promo $promo, FCom_Sales_Model_Cart $cart, array $action,
                                                 array $conditionsResult, array &$actionsResult)
    {
        $combineStrategy = $this->BConfig->get('modules/FCom_Promo/combine_strategy', 'max'); //,compound
        //TODO: remove defaults
        $scope = !empty($action['scope']) ? $action['scope'] : 'whole_order';
        $amountType = !empty($action['type']) ? $action['type'] : 'pcnt'; //,amt,fixed
        $amount = !empty($action['value']) ? $action['value'] : 10;
        $localeHlp = $this->BLocale;
        $items = [];
        switch ($scope) {
            case 'whole_order':
                $items = $cart->items();
                break;

            case 'cond_prod':
                $items = $conditionsResult['items'];
                break;

            case 'other_prod':
                $skus = array_flip($action['skus']);
                foreach ($cart->items() as $item) {
                    if ($item->get('auto_added')) {
                        continue;
                    }
                    if (in_array($item->get('product_sku'), $skus)) {
                        $items[] = $item;
                    }
                }
                break;

            case 'attr_combination':
                $cart->loadProducts();
                foreach ($cart->items() as $item) {
                    if ($item->get('auto_added')) {
                        continue;
                    }
                    $product = $item->getProduct();
                    $variantFields = $item->getData('variant_fields');
                    if ($variantFields) {
                        $product->setReadOnly()->set($variantFields);
                    }
                    if ($this->FCom_Promo_Main->validateProductConditionCombination($product, $action)) {
                        $items[] = $item;
                    }
                }
                break;
        }

        $totalAmount = 0;
        $percent = 0;
        $totalDiscount = 0;
        $rowTotals = [];
        foreach ($items as $i => $item) {
            $rowTotals[$i] = $item->get('row_total');
            if (!empty($actionsResult['items'][$i]['row_discount'])) {
                $rowTotals[$i] -= $actionsResult['items'][$i]['row_discount'];
            }
            $totalAmount += $rowTotals[$i];
        }
        if ($totalAmount) {
            if ($amountType === 'pcnt') {
                $percent       = $amount / 100;
                $totalDiscount = (float)$localeHlp->roundCurrency($totalAmount * $percent);
            } elseif ($amountType === 'amt') {
                $percent       = $amount / $totalAmount;
                $totalDiscount = $amount;
            } elseif ($amountType === 'fixed') {
                $percent       = 1 - $amount / $totalAmount;
                $totalDiscount = $totalAmount - $amount;
            }
        }
        if (empty($actionsResult['discount_amount'])) {
            $actionsResult['discount_amount'] = $totalDiscount;
        } else {
            $actionsResult['discount_amount'] += $totalDiscount;
        }
        $itemId = null;
        $remainingDiscount = $totalDiscount;
        $lastItemIdx = null;
        foreach ($items as $i => $item) {
            if ($item->get('auto_added')) {
                continue;
            }
            $rowDiscount = $localeHlp->roundCurrency($rowTotals[$i] * $percent);
            if (empty($actionsResult['items'][$i]['row_discount'])) {
                $actionsResult['items'][$i]['row_discount_percent'] = $percent * 100;
                $actionsResult['items'][$i]['row_discount']         = $rowDiscount;
            } else {
                $actionsResult['items'][$i]['row_discount_percent'] += min(100, $percent * 100);
                $actionsResult['items'][$i]['row_discount']         += min($rowTotals[$i], $rowDiscount);
            }
            $lastItemIdx = $i;
            $remainingDiscount -= $rowDiscount;
        }
        if ($lastItemIdx && $remainingDiscount) {
            $actionsResult['items'][$lastItemIdx]['row_discount'] += $remainingDiscount; // rounding error fix
        }

        $actionsResult['promos'][$promo->id()]['discount_amount'] = $totalDiscount;
    }

    protected function _calcShippingDiscount(FCom_Promo_Model_Promo $promo, FCom_Sales_Model_Cart $cart, array $action,
                                                 array $conditionsResult, array &$actionsResult)
    {
        if (!empty($action['methods'])) {
            if (!in_array($cart->get('shipping_method'), $action['methods'])) {
                return;
            }
        }
        switch ($action['type']) {
            case 'free':
                $actionsResult['shipping_discount'] = $cart->get('shipping_price');
                $actionsResult['shipping_free'] = 1;
                break;

            case 'pcnt': //TODO: account for previous shipping discounts?
                $discount = $cart->get('shipping_price') * $action['value'] / 100;
                $actionsResult['shipping_discount'] = $this->BLocale->roundCurrency($discount);
                $actionsResult['shipping_free'] = $actionsResult['shipping_discount'] == $cart->get('shipping_price');
                break;

            case 'amt':
                $actionsResult['shipping_discount'] = min($action['value'], $cart->get('shipping_price'));
                $actionsResult['shipping_free'] = $actionsResult['shipping_discount'] == $cart->get('shipping_price');
                break;
        }

        $actionsResult['promos'][$promo->id()]['shipping_discount'] = $actionsResult['shipping_discount'];
        $actionsResult['promos'][$promo->id()]['shipping_free'] = $actionsResult['shipping_free'];
    }

    protected function _calcFreeProduct(FCom_Promo_Model_Promo $promo, FCom_Sales_Model_Cart $cart, array $action,
                                            array $conditionsResult, array &$actionsResult)
    {
        /*
        #foreach ($conditionsResult['items'] as $item) {
        #    $action['related_item_ids'][] = $item->id();
        #}

        $action['promo_id'] = $promo->id();
        $action['title'] = $promo->get('customer_label');
        $action['description'] = $promo->get('customer_details');

        #$actionsResult['free_items'][] = $action;
        */

        $actionsResult['promos'][$promo->id()]['details']['free_items'] = $action;
    }

    /**
     * @param FCom_Sales_Model_Cart $cart
     * @param array $actionsResult
     * @return $this
     */
    public function applyActions(FCom_Sales_Model_Cart $cart, array $actionsResult)
    {
        $promoCartRows = $this->orm()->where('cart_id', $cart->id())->find_many_assoc('promo_id');

        $pcIdsToDelete = [];
        foreach ($promoCartRows as $row) {
            if (empty($actionsResult['promos'][$row->id()])) {
                $pcIdsToDelete[] = $row->id();
            }
        }
        if (!empty($pcIdsToDelete)) {
            $this->delete_many(['id' => $pcIdsToDelete]);
        }

        $itemTypeMatched = FCom_Promo_Model_PromoCartItem::TYPE_MATCHED;
        $itemTypeAdded = FCom_Promo_Model_PromoCartItem::TYPE_ADDED;

        $pciHlp = $this->FCom_Promo_Model_PromoCartItem;

        $promoItemRows = $pciHlp->orm()->where('cart_id', $cart->id())->find_many();
        $promoCartItems = [];
        foreach ($promoItemRows as $row) {
            $promoCartItems[$row->get('promo_id')][$row->get('item_type')][$row->get('cart_item_id')] = $row;
        }

        foreach ($cart->items() as $itemId => $item) {
            if ($item->get('auto_added')) {
                $found = false;
                foreach ($promoCartItems as $promoId => $pcis) {
                    if (!empty($pcis[$itemTypeAdded][$itemId])) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $cart->removeItem($item);
                }
            }
        }

        $pcItemIdsToDelete = [];
        foreach ($actionsResult['promos'] as $promoId => $action) {
            if (empty($promoCartRows[$promoId])) {
                $action['promo_id'] = $promoId;
                $action['cart_id'] = $cart->id();
                $promoCart = $this->create($action);
            } else {
                $freeItems = $promoCartRows[$promoId]->getData('free_items');
                $newSkusLength = !empty($freeItems['sku']) ? sizeof($freeItems['sku']) : 0;
                $oldSkusLength = !empty($promoCartItems[$promoId][$itemTypeAdded]) ? sizeof($promoCartItems[$promoId][$itemTypeAdded]) : 0;
                if ($newSkusLength > $oldSkusLength) {
                    $action['details']['all_added'] = false;
                }
                $promoCart = $promoCartRows[$promoId]->set($action);
            }

            if (!empty($action['details'])) {
                $promoCart->setData($action['details']);
            }
            $promoCart->save();

            if (!empty($promoCartItems[$promoId][$itemTypeMatched])) {
                foreach ($promoCartItems[$promoId][$itemTypeMatched] as $cartItemId => $pci) {
                    if (empty($action['matched_items'][$cartItemId])) {
                        $pcItemIdsToDelete[] = $pci->id();
                    }
                }
            }
            foreach ($action['matched_items'] as $itemId) {
                if (empty($promoCartItems[$promoId][$itemTypeMatched][$itemId])) {
                    $pciHlp->create([
                        'promo_cart_id' => $promoCart->id(),
                        'promo_id' => $promoId,
                        'cart_id' => $cart->id(),
                        'cart_item_id' => $itemId,
                        'item_type' => $itemTypeMatched,
                    ])->save();
                }
            }
        }

        if (!empty($pcItemIdsToDelete)) {
            $pciHlp->delete_many(['id' => $pcItemIdsToDelete]);
        }

        return $this;
    }

    public function addFreeItem($data, FCom_Sales_Model_Cart $cart)
    {
        if (empty($data['promo'])) {
            throw new BException('Invalid request, missing promo id');
        }

        $promo = $this->FCom_Promo_Model_Promo->load($data['promo']);
        if (!$promo) {
            throw new BException('Invalid promo ID');
        }

        $pc = $this->loadWhere(['cart_id' => $cart->id(), 'promo_id' => $promo->id()]);
        if (!$pc) {
            throw new BException('Invalid cart promo ID');
        }

        $freeItems = $pc->getData('free_items');
        if (empty($freeItems['sku'])) {
            throw new BException('Invalid cart promo type');
        }

        $sku = !empty($data['all']) ? null : (!empty($data['sku']) ? $data['sku'] : null);
        if ($sku && !in_array($sku, $freeItems['sku'])) {
            throw new BException('Invalid free item sku');
        }

        $pciHlp = $this->FCom_Promo_Model_PromoCartItem;
        $pciAdded = $pciHlp->orm('pci')
            ->where('pci.promo_cart_id', $pc->id())
            ->where('pci.item_type', FCom_Promo_Model_PromoCartItem::TYPE_ADDED)
            ->join('FCom_Sales_Model_Cart_Item', ['ci.id', '=', 'pci.cart_item_id'], 'ci')
            ->select('pci.*')
            ->select('ci.product_sku')
            ->find_many_assoc('product_sku');

        if ($sku && !empty($pciAdded[$sku])) {
            throw new BException('Free item was already added');
        }

        $parentId = !empty($data['parent']) ? (int)$data['parent'] : null;
        $params = [
            'qty' => $freeItems['qty'],
            'auto_added' => 1,
            'parent_item_id' => $parentId,
            'signature' => [
                'promo_cart_id' => $pc->id(),
                'parent' => $parentId,
                'sku' => $sku,
            ],
        ];
        if ($sku) {
            $skus = [$sku];
            if (sizeof($freeItems['sku']) - sizeof($pciAdded) == 1) {
                $pc->setData('all_added', 1)->save();
            }
        } else {
            $skus = array_diff($freeItems['sku'], array_keys($pciAdded));
            $pc->setData('all_added', 1)->save();
        }

        $products = $this->FCom_Catalog_Model_Product->orm()->where_in('product_sku', $skus)->find_many_assoc('product_sku');
        foreach ($products as $sku => $product) {
            $params['signature']['sku'] = $sku;
            $cartItem = $cart->addProduct($product, $params);
            $pci = $pciHlp->create([
                'promo_cart_id' => $pc->id(),
                'promo_id' => $promo->id(),
                'cart_id' => $cart->id(),
                'cart_item_id' => $cartItem->id(),
                'item_type' => FCom_Promo_Model_PromoCartItem::TYPE_ADDED,
            ])->setData([
                'sku' => $sku,
            ])->save();
        }

        return $this;
    }

    public function removeFreeItem(FCom_Sales_Model_Cart $cart, $item)
    {
        if (is_numeric($item)) {
            $item = $cart->childById('items', $item);
        }
        $freeItemsAdded = $cart->getData('free_items_added');

        $cart->removeItem($item);

        $idx = $cart->getData('free_item_details/idx');
        unset($freeItemsAdded[$idx][$item->id()]);

        $cart->setData('free_items_added', $freeItemsAdded);
    }
}