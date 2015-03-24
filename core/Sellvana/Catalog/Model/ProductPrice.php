<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Model_ProductPrice
 *
 * @property int   $id
 * @property int   $product_id
 * @property int   $group_id
 * @property float $base_price
 * @property float $sale_price
 * @property int   $qty
 */
class Sellvana_Catalog_Model_ProductPrice
    extends FCom_Core_Model_Abstract
{
    protected static $_table     = "fcom_product_price";
    protected static $_origClass = __CLASS__;

    const TYPE_BASE = "base",
        TYPE_MAP = "map",
        TYPE_MSRP = "msrp",
        TYPE_SALE = "sale",
        TYPE_TIER = "tier",
        TYPE_COST = "cost",
        TYPE_PROMO = "promo";

    protected static $_fieldOptions = [
        'price_types'     => [
            self::TYPE_BASE  => "Base Price",
            self::TYPE_MAP   => "MAP",
            self::TYPE_MSRP  => "MSRP",
            self::TYPE_SALE  => "Sale Price",
            self::TYPE_TIER  => "Tier Price",
            self::TYPE_COST => "Cost",
            self::TYPE_PROMO => "Promo Price",
        ],
        'editable_prices' => [
            'base',
            'map',
            'msrp',
            'sale',
            'tier'
        ],
        'price_relation_options' => [
            "base" => [['value' => 'cost', 'label' => 'Cost'], ['value' => 'msrp', 'label' => 'MSRP']],
            "cost" => [['value' => 'base', 'label' => 'Base price']],
            "sale" => [['value' => 'cost', 'label' => 'Cost'], ['value' => 'base', 'label' => 'Base price']],
            "tier" => [['value' => 'cost', 'label' => 'Cost'], ['value' => 'base', 'label' => 'Base price']]
        ],
        'operation_options' => [
            ['value' => '=$', 'label' => "Fixed price"],
            ['value' => '+$', 'label' => "Add amount to"],
            ['value' => '-$', 'label' => "Subtract amount from"],
            ['value' => '+%', 'label' => "Add percent of"],
            ['value' => '-%', 'label' => "Subtract percent from"]
        ],
    ];
    const SALE_DATE_SEPARATOR = ' / ';

    /**
     * @var Sellvana_Catalog_Model_Product
     */
    protected $_product;

    public function setProduct($product)
    {
        $this->_product = $product;
        return $this;
    }

    /**
     * @param Sellvana_Catalog_Model_Product $product
     * @param int                            $qty
     * @param int                            $customer_group_id
     * @param int                            $site_id
     * @param string                         $currency_code
     * @param string                         $date
     * @return Sellvana_Catalog_Model_ProductPrice[]
     * @throws BException
     */
    public function getProductPrices($product, $qty = null, $customer_group_id = null, $site_id = null,
        $currency_code = null, $date = null)
    {
        $orm = $this->orm('tp')
                    ->where('product_id', $product->id());
        if ($date) {
            $orm->where_complex([
                'OR' => [
                    'valid_from IS NULL AND valid_to IS NULL',
                    ['(? BETWEEN valid_from AND valid_to)', $date]
                ]
            ]);
        }
        if (!empty($qty)) {
            $orm->where([['qty <= ?', $qty]]); // tier prices up to provided qty
        }
        if (!empty($customer_group_id)) {
            $orm->where('customer_group_id', $customer_group_id);
        }
        if (!empty($site_id)) {
            $orm->where('site_id', $site_id);
        }
        if (!empty($currency_code)) {
            $orm->where('currency_code', $currency_code);
        }

        $prices = $orm->find_many();
        //if (!empty($prices)) {
        //    $salePrice = (float) $product->get('sale_price');
        //    $basePrice = (float) $product->get('base_price');
        //    $price     = $salePrice? $salePrice: $basePrice;
        //    #$this->BDebug->dump($tiers);
        //    #var_dump($salePrice, $basePrice, $price);
        //    foreach ($prices as $p) {
        //        $p->set('save_percent', ceil((1 - $p->get('price') / $price) * 100));
        //    }
        //}

        return $prices? $this->BDb->many_as_array($prices): [];
    }

    /**
     * @param Sellvana_Catalog_Model_Product $product
     * @param string                         $price_type
     * @param int                            $qty
     * @param int                            $customerGroupId
     * @param int                            $siteId
     * @param string                         $currencyCode
     * @return Sellvana_Catalog_Model_ProductPrice
     */
    public function getPriceModel($product, $price_type, $qty = 1, $customerGroupId = null, $siteId = null,
        $currencyCode = null)
    {
        $price = $this->load([
            'product_id'        => $product->id(),
            'price_type'        => $price_type,
            'customer_group_id' => $customerGroupId,
            'site_id'           => $siteId,
            'qty'               => $qty,
            'currency_code'     => $currencyCode
        ], true);

        return $price;
    }

    /**
     * @param int    $variant_id
     * @param string $type
     * @return Sellvana_Catalog_Model_ProductPrice
     * @throws BException
     */
    public function getVariantPriceModel($variant_id, $type = self::TYPE_BASE)
    {
        /** @var Sellvana_Catalog_Model_ProductPrice $price */
        $price = $this->load(['variant_id' => $variant_id, 'price_type' => $type], true);

        return $price;
    }

    /**
     * @param float  $price
     * @param int    $variant_id
     * @param int    $product_id
     * @param string $type
     */
    public function saveVariantPriceModel($price, $variant_id, $product_id, $type = self::TYPE_BASE)
    {
        $priceModel = $this->getVariantPriceModel($variant_id);
        if (!$priceModel) {
            $priceModel = $this->create();
        }

        $priceModel->set([
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'amount'      => (float) $price,
            'price_type' => $type
        ])->save();
    }

    public function onBeforeSave()
    {
        if ($this->get('sale_period')) {
            $salePeriod = explode(self::SALE_DATE_SEPARATOR, $this->get('sale_period'));
            $count      = count($salePeriod);
            if ($count) {
                $this->set('valid_from', trim($salePeriod[0]));
            }
            if ($count > 1) {
                $this->set('valid_to', trim($salePeriod[1]));
            }
        }

        return parent::onBeforeSave();
    }

    public function onAfterLoad()
    {
        if ($this->get('valid_from')) {
            $salePeriod = [$this->get('valid_from'), $this->get('valid_to')];
            $this->set('sale_period', join(self::SALE_DATE_SEPARATOR, $salePeriod));
        }
        parent::onAfterLoad();
    }

    public function collectProductsPrices($products, $context = null)
    {
        if (!$products) {
            return $this;
        }
        $productsById = [];
        /** @var Sellvana_Catalog_Model_Product $p */
        foreach ($products as $p) {
            $productsById[$p->id()] = $p;
        }
        /** @var BORM $orm */
        $orm = $this->orm()->where_in('product_id', array_keys($productsById));

        if (isset($context['site_id'])) {
            if (false === $context['site_id']) {
                $orm->where_null('site_id');
            } else {
                $orm->where_complex(['OR' => [
                    'site_id is NULL',
                    'site_id' => $context['site_id'],
                ]]);
            }
        }

        if (isset($context['customer_group_id'])) {
            if (false === $context['customer_group_id']) {
                $orm->where_null('customer_group_id');
            } else {
                $orm->where_complex(['OR' => [
                    'customer_group_id is NULL',
                    'customer_group_id' => $context['customer_group_id'],
                ]]);
            }
        }

        if (isset($context['currency_code'])) {
            if (false === $context['currency_code']) {
                $orm->where_null('currency_code');
            } else {
                $orm->where_complex(['OR' => [
                    'currency_code is NULL',
                    'currency_code' => $context['currency_code'],
                ]]);
            }
        }

        if (isset($context['date'])) {
            $orm->where_complex(['OR' => [
                'valid_from IS NULL AND valid_to IS NULL',
                ['(? BETWEEN valid_from AND valid_to)', $context['date']],
            ]]);
        }

        if (empty($context['variants'])) {
            $orm->where_null('variant_id');
        }

        $priceRows = $orm->order_by_asc('qty')->find_many_assoc('id');
        $prices = [];
        /** @var static $r */
        foreach ($priceRows as $rId => $r) {
            $pId = $r->get('product_id');
            $vId = $r->get('variant_id') ?: 0;
            $type = $r->get('price_type');
            $idx = ($r->get('site_id') ?: '*')
                . ':' . ($r->get('customer_group_id') ?: '*')
                . ':' . ($r->get('currency_code') ?: '*');
            switch ($type) {
                case 'promo':
                    $prices[$pId][$vId][$type][$idx][$r->get('promo_id')] = $r;
                    break;
                case 'tier':
                    $prices[$pId][$vId][$type][$idx][$r->get('qty')] = $r;
                    break;
                default:
                    $prices[$pId][$vId][$type][$idx] = $r;
            }
            $r->setProduct($productsById[$pId]);
        }
        /** @var Sellvana_Catalog_Model_Product $p */
        foreach ($products as $p) {
            $p->setPriceModels(!empty($prices[$p->id()]) ? $prices[$p->id()] : false);
        }
        return $this;
    }

    /**
     * Calculate result price based on base amount, relative amount and operation
     *
     * @param float $value1
     * @param float $value2
     * @param string $op
     * @return float
     */
    public function applyPriceOperation($value1, $value2, $op)
    {
        $result = $value1;
        switch ($op) {
            case '=$': $result = $value2; break;
            case '+$': $result += $value2; break;
            case '-$': $result -= $value2; break;
            case '+%': $result += $value1 * $value2 / 100; break;
            case '-%': $result -= $value1 * $value2 / 100; break;
        }
        return $result;
    }

    public function isValid($date = true)
    {
        if (true === $date) {
            $date = $this->BDb->now();
        }
        $from = $this->get('valid_from');
        $to = $this->get('valid_to');
        return (null === $from || $from <= $date) && (null === $to || $to >= $date);
    }

    public function getPrice($basePrice = null)
    {
        $op = $this->get('operation');
        if (!$op || '=$' === $op) {
            return $this->get('amount');
        }

        if (!$this->_product) {
            return null;
        }

        if (null === $basePrice) {
            $baseField = $this->get('base_field');
            if (!$baseField) {
                return null;
            }

            $baseModel = $this->_product->getPriceModelByType($baseField, [
                'site_id' => $this->get('site_id'),
                'customer_group_id' => $this->get('customer_group_id'),
                'currency_code' => $this->get('currency_code'),
            ]);
            if (!$baseModel) {
                return null;
            }
            $basePrice = $baseModel->getPrice();
        }

        return $this->applyPriceOperation($basePrice, $this->get('amount'), $op);
    }

    public function parseAndSaveDefaultPrices(Sellvana_Catalog_Model_Product $product)
    {
        foreach (['base', 'sale', 'cost', 'msrp', 'map'] as $f) {
            $v = $product->get('price.' . $f);
            if (null !== $v) {
                $priceModel = $this->orm()
                    ->where('product_id', $product->id())->where('price_type', $f)->where_null('variant_id')
                    ->where_null('site_id')->where_null('customer_group_id')->where_null('currency_code')
                    ->find_one();
                if ($priceModel) {
                    if (false === $v || '-' === $v) {
                        $priceModel->delete();
                        continue;
                    }
                } else {
                    $priceModel = $this->create([
                        'product_id' => $product->id(),
                        'price_type' => $f,
                    ]);
                }
                $priceModel->set($this->_parsePriceField($v))->save();
            }
        }
        $tiers = $product->get('price.tiers');
        if ($tiers) {
            if (is_string($tiers)) {
                $tiersArr = explode(';', $tiers);
                $tiers = [];
                foreach ($tiersArr as $t1) {
                    $t2 = explode(':', $t1);
                    $tiers[trim($t2[0])] = trim($t2[1]);
                }
            }
            /** @var static[] $priceModels */
            $priceModels = $this->orm()
                ->where('product_id', $product->id())->where('price_type', 'tier')->where_null('variant_id')
                ->where_null('site_id')->where_null('customer_group_id')->where_null('currency_code')
                ->find_many_assoc('qty');
            foreach ($tiers as $tier => $v) {
                if (!empty($priceModels[$tier])) {
                    if (false === $v || '-' === $v) {
                        $priceModels[$tier]->delete();
                        continue;
                    }
                } else {
                    $priceModels[$tier] = $this->create([
                        'product_id' => $product->id(),
                        'price_type' => 'tier',
                        'qty' => $tier,
                    ]);
                }
                $priceModels[$tier]->set($this->_parsePriceField($v))->save();
            }
        }
    }

    protected function _parsePriceField($value)
    {
        if (is_numeric($value)) {
            return [
                'operation' => '=$',
                'amount' => $value,
                'base_field' => null,
            ];
        } elseif (is_string($value) && preg_match('#^(base|sale|cost|msrp|map)([+-])([0-9.]+)(%?)$#', $value, $m)) {
            return [
                'operation' => $m[2] . ($m[4] ?: '$'),
                'amount' => $m[3],
                'base_field' => $m[1],
            ];
        } else {
            throw new BException('Invalid price field value');
        }
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_product);
    }
}
