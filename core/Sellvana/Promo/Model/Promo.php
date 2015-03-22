<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Model_Promo
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
 * @property Sellvana_Promo_Model_PromoCart     $Sellvana_Promo_Model_PromoCart
 * @property Sellvana_Promo_Model_PromoMedia    $Sellvana_Promo_Model_PromoMedia
 * @property FCom_Core_Model_MediaLibrary   $FCom_Core_Model_MediaLibrary
 * @property Sellvana_Promo_Model_PromoProduct  $Sellvana_Promo_Model_PromoProduct
 * @property Sellvana_Customer_Model_Customer   $Sellvana_Customer_Model_Customer
 * @property Sellvana_MultiSite_Main            $Sellvana_MultiSite_Main
 * @property Sellvana_Promo_Model_PromoCoupon   $Sellvana_Promo_Model_PromoCoupon
 * @property Sellvana_Catalog_Model_CategoryProduct $Sellvana_Catalog_Model_CategoryProduct
 * @property Sellvana_Promo_Model_PromoDisplay $Sellvana_Promo_Model_PromoDisplay
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_Promo_Main $Sellvana_Promo_Main
 * @property Sellvana_CustomField_Main $Sellvana_CustomField_Main
 */
class Sellvana_Promo_Model_Promo extends FCom_Core_Model_Abstract
{
    const MATCH_ALL = 'all', MATCH_ANY = 'any', MATCH_ALWAYS = 'always';

    const COUPON_TYPE_NONE = 0, COUPON_TYPE_SINGLE = 1, COUPON_TYPE_MULTI = 2;

    const MAX_PRICES_PER_RUN = 10000;

    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo';
    protected static $_fieldOptions = [
        'promo_type' => [
            'cart' => 'Cart',
            'catalog' => 'Catalog',
        ],
        'coupon_type' => [
            0 => "No coupon code required",
            1 => "Single coupon code",
            2 => "Multiple coupon codes",
        ],
        'status' => [
            'template' => 'Template',
            'pending' => 'Pending',
            'active' => 'Active',
            'expired' => 'Expired',
        ],
        'display_index_section' => [
            'regular' => 'Regular',
            'featured' => 'Featured',
            'this_month' => 'This Month',
            'this_week' => 'This Week',
            'today' => 'Today Only',
        ],
        'display_index_type' => [
            'text' => 'Text',
            'cms_block' => 'CMS BLOCK',
        ],
        'conditions_operator' => [
            "always" => "Apply Always",
            "all" => "All Conditions Have to Match",
            "any" => "Any Condition Has to Match",
        ]
    ];

    protected static $_fieldDefaults = [
        'promo_type' => 'cart',
        'status' => 'pending',
        'coupon_type' => 0,
        'display_index' => 0,
        'display_index_order' => 0,
        'display_index_showexp' => 1,
        'display_index_section' => 'regular',
        'display_index_type' => 'text',
        'conditions_operator' => self::MATCH_ALWAYS
    ];

    protected static $_validationConditions = [];

    public function getPromosByCart($cartId)
    {
        return $this->orm('p')
            ->join($this->Sellvana_Promo_Model_PromoCart->table(), "p.id = pc.promo_id", "pc")
            ->where('cart_id', $cartId)
            ->select('p.id')
            ->select('p.description')
            ->find_many();
    }

    public function mediaORM()
    {
        return $this->Sellvana_Promo_Model_PromoMedia->orm('pa')
            ->join($this->FCom_Core_Model_MediaLibrary->table(), ['a.id', '=', 'pa.file_id'], 'a')
            ->select('a.id')->select('a.file_name')->select('a.folder')
            ->where('pa.promo_id', $this->id);
    }

    /**
     * @return Sellvana_Promo_Model_PromoMedia[]
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

        return true;
    }

    public function onAfterSave()
    {
        if ($this->get('promo_type') === 'catalog') {
            $this->processCatalogPromo($this);
        }
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
        if ($this->BModuleRegistry->isLoaded('Sellvana_CustomerGroup')) {
            $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
            if ($customer && ($custGroupId = $customer->get('customer_group'))) {
                $orm->where_raw('FIND_IN_SET(?, customer_group_ids)', [$custGroupId]);
            }
        }

        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite')) {
            $siteData = $this->Sellvana_MultiSite_Main->getCurrentSiteData();
            if ($siteData) {
                $orm->where_raw('FIND_IN_SET(?, site_ids)', [$siteData['id']]);
            }
        }

        $this->BEvents->fire(__METHOD__, ['orm' => $orm]);

        return $orm;
    }

    /**
     * @param array $couponCodes
     * @return Sellvana_Promo_Model_Promo[]
     * @throws BException
     */
    public function findByCouponCodes(array $couponCodes)
    {
        $promos = $this->orm('p')->select('p.*')
            ->join('Sellvana_Promo_Model_PromoCoupon', ['pc.promo_id', '=', 'p.id'], 'pc')
            ->select('pc.code', 'coupon_code')->select('pc.id', 'coupon_id')
            ->where_in('pc.code', $couponCodes)
            ->order_by_asc('p.priority_order')
            ->find_many();
        if (!$promos) {
            return [];
        }
        return $promos;
    }

    public function getPromoDisplayData($asJson = false)
    {
        $results = $this->Sellvana_Promo_Model_PromoDisplay->orm()->where('promo_id', $this->id())->find_many();
        $result = [];
        foreach ($results as $r) {
            $result[] = $r->as_array();
        }

        return $asJson? $this->BUtil->toJson($result): $result;
    }


    public function processCatalogPromo(Sellvana_Promo_Model_Promo $promo)
    {
        $result = $this->calcCatalogPromoProducts($promo);
        $this->updateCatalogPromoProductPrices($promo, $result);
        $this->confirmCombinationPromoPrices(['promo_id' => $promo->id()]); //TODO: to be done in cron if more than 10000
        return $this;
    }

    public function calcCatalogPromoProducts(Sellvana_Promo_Model_Promo $promo)
    {
        $matchType = $promo->getData('conditions/match');
        $conditionRules = $promo->getData('conditions/rules');

        $result = [];
        if (!$conditionRules || !$matchType || $matchType === 'always') {
            //TODO: optimize applying to all products in catalog when a lot of products
            if ($this->BModuleRegistry->isLoaded('Sellvana_CustomField')) {
                $this->Sellvana_CustomField_Main->disable();
            }
            $products = $this->Sellvana_Catalog_Model_Product->orm('p')
                ->clear_columns()
                ->select('p.id', 'product_id')
                ->select("('promo')", 'price_type')
                ->find_many_assoc('product_id');

            if ($this->BModuleRegistry->isLoaded('Sellvana_CustomField')) {
                $this->Sellvana_CustomField_Main->disable(false);
            }
            $result['products'] = $this->BDb->many_as_array($products);
            return $result;
        }

        $context = [
            'match_type' => $matchType,
            'match_any' => $matchType === static::MATCH_ANY
        ];
        foreach ($conditionRules as $condType => $conditions) {
            $context['cond_type'] = $condType;
            foreach ($conditions as $condIdx => $condition) {
                $context['cond_idx'] = $condIdx;
                switch ($condType) {
                    case 'sku':
                        $this->_calcPromoProductsBySku($condition, $context, $result);
                        break;

                    case 'category':
                        $this->_calcPromoProductsByCategory($condition, $context, $result);
                        break;

                    case 'combination':
                        $this->_calcPromoProductsByCombination($condition, $context, $result);
                        break;

                    default:
                        throw new BException('Invalid condition type: ' . $condType);
                }
                if ($context['match_any'] && !empty($result['products'])) {
                    $result['matched_ids'] = array_keys($result['products']);
                }
            }
        }

        return $result;
    }

    public function updateCatalogPromoProductPrices(Sellvana_Promo_Model_Promo $promo, array $data)
    {
        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        /** @var Sellvana_Catalog_Model_ProductPrice[] $existing */
        $existing = $priceHlp->orm('pp') #$this
        ->where('promo_id', $promo->id())->find_many_assoc('product_id');
        $ppIdsToDelete = [];
        /** @var Sellvana_Promo_Model_PromoProduct $epp */
        $promoId = $promo->id();
        foreach ($existing as $pId => $epp) {
            if (empty($data[$pId])) {
                $ppIdsToDelete[] = $epp->id();
            }
        }
        if ($ppIdsToDelete) {
            $priceHlp->delete_many(['id' => $ppIdsToDelete]); #$this
        }
        $ppsToCreate = [];
        $actions = $promo->getData('actions/rules/discount');
        $action = $actions[0];
        $sortOrder = $promo->get('priority_order');
        $fromDate = $promo->get('from_date');
        $toDate = $promo->get('to_date');

        foreach ($data['products'] as $pId => $r) {
            $r['qty'] = $sortOrder;
            $r['amount'] = $action['value'];
            $r['operation'] = $action['type'] === 'pcnt' ? '-%' : '-$';
            $r['valid_from'] = $fromDate;
            $r['valid_to'] = $toDate;
            if (empty($existing[$pId])) {
                $r['product_id'] = $pId;
                $r['promo_id'] = $promoId;
                unset($r['data']);
                $r['data_serialized'] = !empty($r['data']) ? $this->BUtil->toJson($r['data']) : '';
                $ppsToCreate[] = $r;
            } else {
                $existing[$pId]->set($r);
                if (!empty($r['data'])) {
                    $existing[$pId]->setData($r['data']);
                }
                $existing[$pId]->save();
            }
        }
        if ($ppsToCreate) {
            //echo "<pre>"; var_dump($ppsToCreate); exit;
            $priceHlp->create_many($ppsToCreate); #$this
            //TODO: run onAfterCreate?
        }
        return $this;
    }

    public function confirmCombinationPromoPrices(array $where = null, $limit = self::MAX_PRICES_PER_RUN)
    {
        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        $ppsOrm = $priceHlp->orm('pp')->where('price_type', 'promo-pend');
        if ($where) {
            $ppsOrm->where($where);
        }
        if ($limit) {
            $ppsOrm->limit($limit);
        }
        $pps = $ppsOrm->find_many();
        if (!$pps) {
            return;
        }

        $pIds = [];
        foreach ($pps as $pp) {
            $pId = $pp->get('product_id');
            $pIds[$pId] = $pId;
        }
        $products = $this->Sellvana_Catalog_Model_Product->orm('p')
            ->where_in('id', array_values($pIds))->find_many_assoc('id');
        $priceHlp->collectProductsPrices($products);
        $this->Sellvana_Catalog_Model_InventorySku->collectInventoryForProducts($products);
        $hlp = $this->Sellvana_Promo_Main;
        $ppIdsToDelete = [];

        /** @var Sellvana_Catalog_Model_ProductPrice $pp */
        foreach ($pps as $pp) {
            $product = $products[$pp->get('product_id')];
            $conditions = $pp->getData('conditions');
            $matchAny = $pp->getData('match_type') === static::MATCH_ANY;
            $match = $matchAny ? false : true;
            foreach ($conditions as $idx => $condition) {
                $validated = $hlp->validateProductConditionCombination($product, $condition);
                if ($validated && $matchAny) {
                    $match = true;
                    break;
                } elseif (!$validated && !$matchAny) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $pp->set('price_type', 'promo')->save();
            } else {
                $ppIdsToDelete[] = $pp->id();
            }
        }
        if ($ppIdsToDelete) {
            $this->delete_many(['id' => $ppIdsToDelete]);
        }
    }

    protected function _calcPromoProductsBySku(array $condition, array $context, array &$result)
    {
        /** @var BORM $orm */
        $orm = $this->Sellvana_Catalog_Model_Product->orm('p')->where_in('product_sku', $condition['sku']);
        if ($context['match_any'] && !empty($result['matched_ids'])) {
            $orm->where_not_in('p.id', $result['matched_ids']);
        }
        $products = $orm->clear_columns()->select('p.id')->find_many_assoc('id', 'id');
        foreach ($products as $id => $p) {
            if (empty($result['products'][$id]['calc_status'])) {
                $result['products'][$id]['price_type'] = 'promo';
            }
        }
    }

    protected function _calcPromoProductsByCategory(array $condition, array $context, array &$result)
    {
        $categoryIds = $condition['category_id'];
        if (!empty($condition['include_subcategories'])) {
            $categories = $this->Sellvana_Catalog_Model_Category->orm('c')->where_in('id', $categoryIds)->find_many();
            /** @var Sellvana_Catalog_Model_Category $c */
            foreach ($categories as $c) {
                $categoryIds = array_merge($categoryIds, array_keys($c->descendants()));
            }
        }
        /** @var BORM $orm */
        $orm = $this->Sellvana_Catalog_Model_CategoryProduct->orm('cp')->where_in('category_id', $categoryIds);
        if ($context['match_any'] && !empty($result['matched_ids'])) {
            $orm->where_not_in('cp.product_id', $result['matched_ids']);
        }
        $products = $orm->clear_columns()->select('cp.product_id', 'id')->find_many_assoc('id', 'id');
        foreach ($products as $id => $p) {
            if (empty($result['products'][$id]['calc_status'])) {
                $result['products'][$id]['price_type'] = 'promo';
            }
        }
    }

    protected function _calcPromoProductsByCombination(array $condition, array $context, array &$result)
    {
        /** @var BORM $orm */
        $orm = $this->Sellvana_Catalog_Model_Product->orm('p');
        if ($context['match_any'] && !empty($result['matched_ids'])) {
            $orm->where_not_in('p.id', $result['matched_ids']);
        }
        $products = $orm->clear_columns()->select('p.id')->find_many_assoc('id', 'id');
        foreach ($products as $id => $p) {
            if (empty($result['products'][$id]['calc_status'])
                || $result['products'][$id]['price_type'] === 'promo' && !$context['match_any']
            ) {
                $result['products'][$id]['price_type'] = 'promo-pend';
                $result['products'][$id]['data']['match_type'] = $context['match_type'];
                $result['products'][$id]['data']['combinations'][$context['cond_idx']] = $condition;
            }
        }
    }
}
