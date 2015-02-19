<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Model_Promo
 *
 * @property int $id
 * @property string $description
 * @property string $details
 * @property int $manuf_vendor_id
 * @property string $from_date
 * @property string $to_date
 * @property string $status enum('template','pending','active','expired')
 * @property string $buy_type enum('qty','$')
 * @property string $buy_group enum('one','any','all','cat','anyp')
 * @property int $buy_amount
 * @property string $get_type enum('qty','$','%','text','choice','free')
 * @property string $get_group enum('same_prod','same_group','any_group','diff_group')
 * @property int $get_amount
 * @property string $originator enum('manuf','vendor')
 * @property string $fulfillment enum('manuf','vendor')
 * @property string $create_at
 * @property string $update_at
 * @property string $coupon
 *
 * @property FCom_Promo_Model_PromoCart     $FCom_Promo_Model_PromoCart
 * @property FCom_Promo_Model_PromoMedia    $FCom_Promo_Model_PromoMedia
 * @property FCom_Core_Model_MediaLibrary   $FCom_Core_Model_MediaLibrary
 * @property FCom_Promo_Model_PromoProduct  $FCom_Promo_Model_PromoProduct
 * @property FCom_Customer_Model_Customer   $FCom_Customer_Model_Customer
 * @property FCom_MultiSite_Main            $FCom_MultiSite_Main
 * @property FCom_Promo_Model_PromoCoupon   $FCom_Promo_Model_PromoCoupon
 * @property FCom_Catalog_Model_CategoryProduct $FCom_Catalog_Model_CategoryProduct
 */
class FCom_Promo_Model_Promo extends FCom_Core_Model_Abstract
{
    const MATCH_ALL = 'all', MATCH_ANY = 'any';

    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo';
    protected static $_fieldOptions = [
        'status' => [
            'template' => 'Template',
            'pending' => 'Pending',
            'active' => 'Active',
            'expired' => 'Expired',
        ],
    ];

    protected static $_validationConditions = [];

    public function getPromosByCart($cartId)
    {
        return $this->orm('p')
            ->join($this->FCom_Promo_Model_PromoCart->table(), "p.id = pc.promo_id", "pc")
            ->where('cart_id', $cartId)
            ->select('p.id')
            ->select('p.description')
            ->find_many();
    }

    public function mediaORM()
    {
        return $this->FCom_Promo_Model_PromoMedia->orm('pa')
            ->join($this->FCom_Core_Model_MediaLibrary->table(), ['a.id', '=', 'pa.file_id'], 'a')
            ->select('a.id')->select('a.file_name')->select('a.folder')
            ->where('pa.promo_id', $this->id);
    }

    /**
     * @return FCom_Promo_Model_PromoMedia[]
     */
    public function media()
    {
        return $this->mediaORM()->find_many();
    }

    public function onAfterCreate()
    {
        parent::onAfterCreate();
        $this->from_date = gmdate('Y-m-d');
        $this->to_date   = gmdate('Y-m-d', time() + 30 * 86400);
        $this->status    = 'pending';
    }

    public function onBeforeSave()
    {
        parent::onBeforeSave();

        $this->setDate('from_date', $this->get("from_date"));
        $this->setDate('to_date', $this->get("to_date"));
        if (!$this->get("create_at")) {
            $this->set("create_at", date("Y-m-d"));
        }
        $this->set("update_at", date("Y-m-d"));

        return true;
    }

    /**
     * Set date field
     * By default dates are returned as strings, therefore we need to convert them for mysql
     *
     * @param $field
     * @param $fieldDate
     * @return static
     */
    public function setDate($field, $fieldDate)
    {
        $date = strtotime($fieldDate);
        if (-1 != $date) {
            $this->set($field, date("Y-m-d", $date));
        }
        return $this;
    }

    public function getActive()
    {
        return $this->orm()->where('status', 'active')
                ->order_by_desc('buy_amount')
                ->find_many();
    }

    /**
     * @return BORM
     */
    public function findActiveOrm()
    {
        $now = $this->BDb->now();

        $orm = $this->orm('p')
            ->where('status', 'active')
            ->where_raw('((from_date is null or from_date<?) and (to_date is null or to_date>?))', [$now, $now])
            ->order_by_asc('priority_order')
        ;

        //TODO: move to each specific module event observers?
        if ($this->BModuleRegistry->isLoaded('FCom_CustomerGroup')) {
            $customer = $this->FCom_Customer_Model_Customer->sessionUser();
            if ($customer && ($custGroupId = $customer->get('customer_group'))) {
                $orm->where_raw('FIND_IN_SET(?, customer_group_ids)', [$custGroupId]);
            }
        }

        if ($this->BModuleRegistry->isLoaded('FCom_MultiSite')) {
            $siteData = $this->FCom_MultiSite_Main->getCurrentSiteData();
            if ($siteData) {
                $orm->where_raw('FIND_IN_SET(?, site_ids)', [$siteData['id']]);
            }
        }

        $this->BEvents->fire(__METHOD__, ['orm' => $orm]);

        return $orm;
    }

    /**
     * @param array $couponCodes
     * @return FCom_Promo_Model_Promo[]
     * @throws BException
     */
    public function findByCouponCodes(array $couponCodes)
    {
        $promos = $this->orm('p')->select('p.*')
            ->join('FCom_Promo_Model_PromoCoupon', ['pc.promo_id', '=', 'p.id'], 'pc')
            ->where_in('pc.code', $couponCodes)
            ->order_by_asc('p.priority_order')
            ->find_many();
        if (!$promos) {
            return [];
        }
        return $promos;
    }

    protected function _compareValues($v1, $v2, $op)
    {
        switch ($op) {
            case 'gt':          return $v1 > $v2;
            case 'gte':         return $v1 >= $v2;
            case 'lt':          return $v1 < $v2;
            case 'lte':         return $v1 <= $v2;
            case 'eq':          return $v1 == $v2;
            case 'neq':         return $v1 != $v2;
            case 'is':          return in_array($v1, (array)$v2, false);
            case 'in':          return in_array($v1, (array)$v2, false);
            case 'is_not':      return !in_array($v1, (array)$v2, false);
            case 'not_in':      return !in_array($v1, (array)$v2, false);
            case 'empty':       return $v1 === null || $v1 === false || $v1 === '';
            case 'contains':    return strpos($v1, $v2) !== false;
            case 'between':     return $v1 >= $v2[0] && $v1 <= $v2[1];
            default:            throw new BException('Invalid operator: '. $op);
        }
    }

    protected function _validateProductConditionCombination(FCom_Catalog_Model_Product $product, array $condition)
    {
        $finalMatch = $condition['match'] === static::MATCH_ALL ? true : false;
        foreach ($condition['fields'] as $fieldCond) {
            list($fieldSource, $fieldCode) = explode('.', $fieldCond['field']);

            switch ($fieldSource) {
                case 'field':
                case 'static':
                    $value = $product->get($fieldCode);
                    break;

                case 'stock':
                    $value = $product->getInventoryModel()->get($fieldCode);
                    break;

                default:
                    throw new BException('Invalid field source: ' . $fieldSource);
            }

            $match = $this->_compareValues($value, $fieldCond['value'], $fieldCond['filter']);

            if ($condition['match'] === static::MATCH_ANY && $match) {
                $finalMatch = true;
                break;
            } elseif ($condition['match'] === static::MATCH_ALL && !$match) {
                $finalMatch = false;
                break;
            }
        }
        return $finalMatch;
    }

    public function validateForCart(FCom_Sales_Model_Cart $cart)
    {
        $matchType = $cart->getData('conditions/match') ?: static::MATCH_ANY; //TODO: remove default after testing
        $result = [
            'match' => $matchType === static::MATCH_ALL ? true : false,
            'items' => [],
        ];
        if ($this->get('status') !== 'active' || $this->get('promo_type') !== 'cart') {
            return $result;
        }
        $now = $this->BDb->now();
        if (!(
            ($this->BUtil->isEmptyDate($this->get('from_date')) || $this->get('from_date') < $now)
            && ($this->BUtil->isEmptyDate($this->get('to_date')) || $this->get('to_date') > $now)
        )) {
            return $result;
        }

        $conditionRules = $this->getData('conditions/rules');
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
                        $match = $this->_validateCartConditionSkus($cart, $condition, $result);
                        break;

                    case 'category':
                        $match = $this->_validateCartConditionCategories($cart, $condition, $result);
                        break;

                    case 'total':
                        $match = $this->_validateCartConditionTotal($cart, $condition, $result);
                        break;

                    case 'combination':
                        $match = $this->_validateCartConditionCombination($cart, $condition, $result);
                        break;

                    case 'shipping':
                        $match = $this->_validateCartConditionShipping($cart, $condition, $result);
                        break;

                    default:
                        throw new BException('Invalid condition type: ' . $conditionType);
                }
                if ($matchType === static::MATCH_ANY && $match) {
                    $result['match'] = true;
                    #break; // don't stop loop to collect all matched products to use in actions
                } elseif ($matchType === static::MATCH_ALL && !$match) {
                    $result['match'] = false;
                    #break;
                }
            }
        }
        return $result;
    }

    protected function _validateCartConditionSkus(FCom_Sales_Model_Cart $cart, array $condition, array &$result)
    {
        if (empty($condition['sku'])) {
            return false;
        }
        $skus = array_flip((array)$condition['sku']);
        $total = 0;
        $items = [];
        /** @var FCom_Sales_Model_Cart_Item $item */
        foreach ($cart->items() as $item) {
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
        if ($this->_compareValues($total, $conditionTotalValue, $condition['filter'])) {
            foreach ($items as $item) {
                $result['items'][$item->id()] = $item;
            }
            return true;
        } else {
            return false;
        }
    }

    protected function _validateCartConditionCategories(FCom_Sales_Model_Cart $cart, array $condition, array &$result)
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
        if ($this->_compareValues($total, $conditionTotalValue, $condition['filter'])) {
            foreach ($items as $item) {
                $result['items'][$item->id()] = $item;
            }
            return true;
        } else {
            return false;
        }
    }

    protected function _validateCartConditionTotal(FCom_Sales_Model_Cart $cart, array $condition, array &$result)
    {
        $conditionTotalValue = !empty($condition['value']) ? $condition['value'] : 0;
        if ($this->_compareValues($cart->get('subtotal'), $conditionTotalValue, $condition['filter'])) {
            foreach ($cart->items() as $item) {
                $result['items'][$item->id()] = $item;
            }
            return true;
        } else {
            return false;
        }
    }

    protected function _validateCartConditionCombination(FCom_Sales_Model_Cart $cart, array $condition, array &$result)
    {
        if (empty($condition['fields'])) {
            return false;
        }

        $cart->loadProducts();
        $match = false;
        foreach ($cart->items() as $item) {
            $product = $item->product();
            $variantFields = $item->getData('variant_fields');
            if ($variantFields) {
                $product->setReadOnly()->set($variantFields);
            }
            if ($this->_validateProductConditionCombination($product, $condition)) {
                $result['items'][$item->id()] = $item;
                $match = true;
            }
        }
        return $match;
    }

    protected function _validateCartConditionShipping(FCom_Sales_Model_Cart $cart, array $condition, array &$result)
    {
        if (empty($condition['fields'])) {
            return false;
        }
        $address = $cart->getShippingAddress();
        foreach ($condition['fields'] as $fieldCond) {
            switch ($fieldCond['field']) {
                case 'methods':
                    $res = $this->_compareValues($cart->get('shipping_method'), $fieldCond['value'], $fieldCond['filter']);
                    break;

                case 'country':
                    $res = $this->_compareValues($address->get('country'), $fieldCond['value'], $fieldCond['filter']);
                    break;

                case 'state': //TODO: 'region'
                    $res = $this->_compareValues($address->get('region'), $fieldCond['value'], $fieldCond['filter']);
                    break;

                case 'zipcode': //TODO: 'postcode'
                    $res = $this->_compareValues($address->get('postcode'), $fieldCond['value'], $fieldCond['filter']);
                    break;

                default:
                    throw new BException('Invalid condition field: ' . $fieldCond['field']);
            }
            if ($condition['match'] === static::MATCH_ANY && $res) {
                return true;
            } elseif ($condition['match'] === static::MATCH_ALL && !$res) {
                return false;
            }
        }
        return $condition['match'] === static::MATCH_ALL ? false : true;
    }

    public function calculateActionsForCart(FCom_Sales_Model_Cart $cart, array $conditionsResult, array &$actionsResult)
    {
        $actions = $this->getData('actions/rules/discount');
        if ($actions) {
            foreach ($actions as $action) {
                $this->_calcCartSubtotalDiscount($cart, $action, $conditionsResult, $actionsResult);
            }
        }

        $actions = $this->getData('actions/rules/shipping');
        if ($actions) {
            foreach ($actions as $action) {
                $this->_calcCartShippingDiscount($cart, $action, $conditionsResult, $actionsResult);
            }
        }

        $actions = $this->getData('actions/rules/free_product');
        if ($actions) {
            foreach ($actions as $action) {
                $this->_calcCartFreeProduct($cart, $action, $conditionsResult, $actionsResult);
            }
        }
        return $this;
    }

    protected function _calcCartSubtotalDiscount(FCom_Sales_Model_Cart $cart, array $action,
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
                    if (in_array($item->get('product_sku'), $skus)) {
                        $items[] = $item;
                    }
                }
                break;

            case 'attr_combination':
                $cart->loadProducts();
                foreach ($cart->items() as $item) {
                    $product = $item->product();
                    $variantFields = $item->getData('variant_fields');
                    if ($variantFields) {
                        $product->setReadOnly()->set($variantFields);
                    }
                    if ($this->_validateProductConditionCombination($product, $action)) {
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
        foreach ($items as $item) {
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
            $totalDiscount -= $rowDiscount;
        }
        if ($lastItemIdx && $totalDiscount) {
            $actionsResult['items'][$lastItemIdx]['row_discount'] += $totalDiscount; // rounding error fix
        }
    }

    protected function _calcCartShippingDiscount(FCom_Sales_Model_Cart $cart, array $action,
                                                 array $conditionsResult, array &$actionsResult)
    {
        switch ($action['type']) {
            case 'free':
                $actionsResult['shipping_free'] = 1;
                break;

            case 'pcnt':
                $discount = $cart->get('shipping_price') * $action['value'] / 100;
                $actionsResult['shipping_discount'] = $this->BLocale->roundCurrency($discount);
                break;

            case 'amt':
                $actionsResult['shipping_discount'] = min($action['value'], $cart->get('shipping_price'));
                break;
        }
    }

    protected function _calcCartFreeProduct(FCom_Sales_Model_Cart $cart, array $action,
                                            array $conditionsResult, array &$actionsResult)
    {
        $result = [
            'free_items' => [],
        ];
        return $result;
    }

    public function getPromoDisplayData($asJson = false)
    {
        // todo

        $result = [
            [
                'id'              => 1,
                'promo_id'        => 1,
                'page_type'       => 'home_page',
                'page_location'   => 'below_product_name',
                'content_type'    => 'html',
                'data_serialized' => json_encode([
                    'html_content' => '<h1>Content</h1>',
                    'match'        => 'always',
                    'conditions'   => [
                        'promo_conditions_match' => 0,
                        'customer_groups'        => 'general'
                    ]
                ])
            ],
            [
                'id'              => 2,
                'promo_id'        => 1,
                'page_type'       => 'cart_page',
                'page_location'   => 'above_description',
                'content_type'    => 'cms_block',
                'data_serialized' => json_encode([
                    'cms_block_handle' => 'block1',
                    'match'            => 'all',
                    'conditions'       => [
                        'customer_groups' => 'all'
                    ]
                ])
            ],
        ];

        return $asJson? $this->BUtil->toJson($result): $result;
    }
}
