<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Model_PromoProduct
 *
 * @property int $id
 * @property int $promo_id
 * @property int $product_id
 * @property int $qty
 * @property boolean $calc_status
 *
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_CategoryProduct $Sellvana_Catalog_Model_CategoryProduct
 * @property Sellvana_Promo_Main $Sellvana_Promo_Main
 * @property Sellvana_Promo_Model_Promo $Sellvana_Promo_Model_Promo
 * @property Sellvana_Promo_Model_PromoProductPrice $Sellvana_Promo_Model_PromoProductPrice
 * @property Sellvana_CustomField_Main $Sellvana_CustomField_Main
 *
 * @deprecated by ProductPrice - delete after testing
 */
class Sellvana_Promo_Model_PromoProduct extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_product';

    const STATUS_PENDING = 0,
        STATUS_CONFIRMED = 1,
        STATUS_UPDATED = 2;

    const MAX_PRICES_PER_RUN = 10000;

    public function processPromo(Sellvana_Promo_Model_Promo $promo)
    {
        $result = $this->calcPromoProducts($promo);
        $this->updatePromoProducts($promo, $result);
        $this->confirmCombinations(['promo_id' => $promo->id()]); //TODO: to be done in cron if more than 1000
        //$this->updateProductPrices([$promo->id()]);
        return $this;
    }

    public function calcPromoProducts(Sellvana_Promo_Model_Promo $promo)
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
                ->select("(" . static::STATUS_CONFIRMED . ")", 'calc_status')
                ->find_many_assoc('product_id');

            if ($this->BModuleRegistry->isLoaded('Sellvana_CustomField')) {
                $this->Sellvana_CustomField_Main->disable(false);
            }
            $result['products'] = $this->BDb->many_as_array($products);
            return $result;
        }

        $context = [
            'match_type' => $matchType,
            'match_any' => $matchType === Sellvana_Promo_Model_Promo::MATCH_ANY
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

    public function updatePromoProducts(Sellvana_Promo_Model_Promo $promo, array $data)
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

        foreach ($data['products'] as $pId => $r) {
            $r['qty'] = $sortOrder;
            $r['amount'] = $action['value'];
            $r['operation'] = $action['type'];
            if (empty($existing[$pId])) {
                $r['price_type'] = $r['calc_status'] ? 'promo' : 'promo-pending';
                $r['product_id'] = $pId;
                $r['promo_id'] = $promoId;
                $r['data_serialized'] = !empty($r['data']) ? $this->BUtil->toJson($r['data']) : '';
                unset($r['data']);
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

    public function confirmCombinations(array $where = null, $limit = self::MAX_PRICES_PER_RUN)
    {
        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        $ppsOrm = $priceHlp->orm('pp')->where('price_type', 'promo-pending');
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
            $matchAny = $pp->getData('match_type') === Sellvana_Promo_Model_Promo::MATCH_ANY;
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
                $result['products'][$id]['calc_status'] = static::STATUS_CONFIRMED;
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
                $result['products'][$id]['calc_status'] = static::STATUS_CONFIRMED;
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
                || $result['products'][$id]['calc_status'] === static::STATUS_CONFIRMED && !$context['match_any']
            ) {
                $result['products'][$id]['calc_status'] = static::STATUS_PENDING;
                $result['products'][$id]['data']['match_type'] = $context['match_type'];
                $result['products'][$id]['data']['combinations'][$context['cond_idx']] = $condition;
            }
        }
    }
}