<?php

/**
 * Class Sellvana_Catalog_Model_ProductPrice
 *
 * @property int   $id
 * @property int   $product_id
 * @property int   $group_id
 * @property float $base_price
 * @property float $sale_price
 * @property int   $qty
 *
 * @property Sellvana_MultiCurrency_Main $Sellvana_MultiCurrency_Main
 * @property Sellvana_MultiSite_Frontend $Sellvana_MultiSite_Frontend
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_CustomerGroups_Model_Group $Sellvana_CustomerGroups_Model_Group
 */
class Sellvana_Catalog_Model_ProductPrice
    extends FCom_Core_Model_Abstract
{
    protected static $_table     = "fcom_product_price";
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['product_id', 'price_type', 'customer_group_id', 'site_id', 'currency_code','qty','variant_id','promo_id'],
        'related'    => [
            'product_id' => 'Sellvana_Catalog_Model_Product.id',
            'customer_group_id' => 'Sellvana_CustomerGroups_Model_Group.id',
            'site_id' => 'Sellvana_MultiSite_Model_Site.id',
            'variant_id' => 'Sellvana_CatalogFields_Model_ProductVariant.id',
            'promo_id' => 'Sellvana_Promo_Model_Promo.id'
        ],
    ];

    const TYPE_BASE = "base",
        TYPE_MAP = "map",
        TYPE_MSRP = "msrp",
        TYPE_SALE = "sale",
        TYPE_TIER = "tier",
        TYPE_COST = "cost",
        TYPE_PROMO = "promo";

    protected static $_fieldOptions = [
        'price_types'     => [
            self::TYPE_BASE  => (("Base Price")),
            self::TYPE_MAP   => (("MAP")),
            self::TYPE_MSRP  => (("MSRP")),
            self::TYPE_SALE  => (("Sale Price")),
            self::TYPE_TIER  => (("Tier Price")),
            self::TYPE_COST  => (("Cost")),
            self::TYPE_PROMO => (("Promo Price")),
        ],
        'editable_prices' => [
            'base',
            'map',
            'msrp',
            'sale',
            'tier',
            'cost'
        ],
        'price_relation_options' => [
            "base" => [
                ['value' => 'cost', 'label' => (('Cost'))],
                ['value' => 'msrp', 'label' => (('MSRP'))],
            ],
            "cost" => [
                ['value' => 'base', 'label' => (('Base'))],
                ['value' => 'sale', 'label' => (('Sale'))],
            ],
            "sale" => [
                ['value' => 'cost', 'label' => (('Cost'))],
                ['value' => 'base', 'label' => (('Base'))],
            ],
            "tier" => [
                ['value' => 'cost', 'label' => (('Cost'))],
                ['value' => 'base', 'label' => (('Base'))],
                ['value' => 'sale', 'label' => (('Sale'))],
            ],
            "map" => [
                ['value' => 'base', 'label' => (('Base'))],
                ['value' => 'msrp', 'label' => (('MSRP'))],
            ],
            "msrp" => [
                ['value' => 'cost', 'label' => (('Cost'))],
                ['value' => 'base', 'label' => (('Base'))],
            ],
            "promo" => [
                ['value' => 'catalog', 'label' => (('Catalog Price'))],
                ['value' => 'cost', 'label' => (('Cost'))],
                ['value' => 'base', 'label' => (('Base'))],
                ['value' => 'sale', 'label' => (('Sale'))],
                ['value' => 'msrp', 'label' => (('MSRP'))],
            ],
        ],
        'operation_options' => [
            ['value' => '=$', 'label' => (("Fixed"))],
            ['value' => '*$', 'label' => (("Times"))],
            ['value' => '+$', 'label' => (("Add to"))],
            ['value' => '-$', 'label' => (("Subtract from"))],
            ['value' => '*%', 'label' => (('Set % of'))],
            ['value' => '+%', 'label' => (('Add % to'))],
            ['value' => '-%', 'label' => (('Subtract % from'))],
        ],
    ];

    protected static $_fieldDefaults = [
        'amount' => 0,
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
    public function getProductPrices($product, $variant_id = null, $qty = null, $customer_group_id = null, $site_id = null,
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

        if (!empty($variant_id)) {
            $orm->where('variant_id', $variant_id);
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

        $modHlp = $this->BModuleRegistry;
        if ($modHlp->isLoaded('Sellvana_MultiSite')) {
            $orm->order_by_asc('(ifnull(customer_group_id,0))');
        }
        if ($modHlp->isLoaded('Sellvana_CustomerGroups')) {
            $orm->order_by_asc('(ifnull(site_id,0))');
        }
        if ($modHlp->isLoaded('Sellvana_MultiCurrency')) {
            $orm->order_by_asc("(ifnull(currency_code,''))");
        }

        $priceModels = $orm->order_by_asc("(case tp.price_type
            when 'base'  then 1
            when 'cost'  then 2
            when 'map'   then 3
            when 'msrp'  then 4
            when 'sale'  then 5
            when 'tier'  then 6
            when 'promo' then 7
            else 99 end)")->order_by_asc("tp.qty")->find_many();
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

        if (!empty($priceModels[0])) {
            $priceModels[0]->set('is_base_price', 1);
        }

        $prices = $priceModels ? $this->BDb->many_as_array($priceModels) : [];
        return $prices;
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
        if (!parent::onBeforeSave()) {
            return false;
        }

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

        return true;
    }

    public function onAfterLoad()
    {
        if ($this->get('valid_from')) {
            $salePeriod = [$this->get('valid_from'), $this->get('valid_to')];
            $this->set('sale_period', join(self::SALE_DATE_SEPARATOR, $salePeriod));
        }
        parent::onAfterLoad();
    }

    /**
     * @param Sellvana_Catalog_Model_Product[] $products
     * @param array $context
     * @return $this
     */
    public function collectProductsPrices($products, $context = [])
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

        if (!empty($context['no_variants'])) {
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
            $p->setPriceModels(!empty($prices[$p->id()]) ? $prices[$p->id()] : []);
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
        switch ($op) {
            case '=$': $result = $value2; break;
            case '*$': $result = $value1 * $value2; break;
            case '+$': $result = $value1 + $value2; break;
            case '-$': $result = $value1 - $value2; break;
            case '*%': $result = $value1 * $value2 / 100; break;
            case '+%': $result = $value1 + $value1 * $value2 / 100; break;
            case '-%': $result = $value1 - $value1 * $value2 / 100; break;
            default: $result = $value1;
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

    public function getPrice($basePrice = null, $currency = null)
    {
        $op = $this->get('operation');

        if (!$op || '=$' === $op) {
            $amount = $this->get('amount');
            if ($this->BModuleRegistry->isLoaded('Sellvana_MultiCurrency')) {
                $priceCurrency = $this->get('currency_code');
                if (!$priceCurrency) {
                    $priceCurrency = null;
                }
                $rate = $this->Sellvana_MultiCurrency_Main->getRate($currency, $priceCurrency);
                if ($rate && $rate != 1) {
                    $amount *= $rate;
                }
            }
            $amount = $this->BLocale->roundCurrency($amount);
            return $amount;
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
            $basePrice = $baseModel->getPrice(null, $currency);
        }

        $amount = $this->applyPriceOperation($basePrice, $this->get('amount'), $op);

        $amount = $this->BLocale->roundCurrency($amount);

        return $amount;
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
                    if ($f === 'sale') {
                        $saleModel = $priceModel;
                    }
                } else {
                    $priceModel = $this->create([
                        'product_id' => $product->id(),
                        'price_type' => $f,
                    ]);
                }
                try {
                    $priceModel->set($this->_parsePriceField($v))->save();
                } catch(Exception $e) {
                    $this->BDebug->logException($e); // probably should not stop import?
                }
            }
        }

        $tiers = $product->get('price.tier');
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
                try {
                    $priceModels[$tier]->set($this->_parsePriceField($v))->save();
                } catch(Exception $e) {
                    $this->BDebug->logException($e); // probably should not stop import?
                }
            }
        }

        $saleFrom = $product->get('price.sale.from_date');
        $saleTo = $product->get('price.sale.to_date');
        if ($saleFrom || $saleTo) {
            if (empty($saleModel)) {
                $saleModel = $this->orm()
                    ->where('product_id', $product->id())->where('price_type', 'sale')->where_null('variant_id')
                    ->where_null('site_id')->where_null('customer_group_id')->where_null('currency_code')
                    ->find_one();
            }
            if ($saleModel) {
                if ($saleFrom) {
                    $saleFrom = new DateTime($saleFrom);
                    $saleModel->set('valid_from', $saleFrom->format("Y-m-d"));
                }
                if ($saleTo) {
                    $saleTo = new DateTime($saleTo);
                    $saleModel->set('valid_to', $saleTo->format("Y-m-d"));
                }
                $saleModel->save();
            }
        }
    }


    /**
     * @param Sellvana_Catalog_Model_ProductPrice[] $priceModels
     * @param string $type
     * @param array $context
     * @param bool $useDefault
     * @return Sellvana_Catalog_Model_ProductPrice
     */
    public function getPriceModelByType(array $priceModels, $type, $context = [], $useDefault = true)
    {
        $variantId = !empty($context['variant_id']) ? $context['variant_id'] : 0;

        if (false === $priceModels || empty($priceModels[$variantId][$type])) {
            return null;
        }

        $prices = $priceModels[$variantId][$type];

        if (!empty($context['default'])) {
            return isset($prices['*:*:*']) ? $prices['*:*:*'] : null;
        }

        static $siteId = null, $customerGroupId = false, $currencyCode = false;
        if (null === $siteId) {
            $modHlp = $this->BModuleRegistry;
            $siteId = false;
            if ($modHlp->isLoaded('Sellvana_MultiSite')) {
                $site = $this->Sellvana_MultiSite_Frontend->getCurrentSite();
                $siteId = $site ? $site->id() : false;
            }
            if ($modHlp->isLoaded('Sellvana_CustomerGroups')) {
                $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
                $customerGroupId = $customer ? $customer->get('customer_group_id')
                    : $this->Sellvana_CustomerGroups_Model_Group->notLoggedInId();
            }
            if ($modHlp->isLoaded('Sellvana_MultiCurrency')) {
                $currency = $this->Sellvana_MultiCurrency_Main->getCurrentCurrency();
                $currencyCode = $currency ?: false;
            }
        }

        if (!empty($context['default'])) {
            $context['site_id'] = '*';
            $context['customer_group_id'] = '*';
            $context['currency_code'] = '*';
        }

        $s = !empty($context['site_id']) ? $context['site_id'] : $siteId;
        $g = !empty($context['customer_group_id']) ? $context['customer_group_id'] : $customerGroupId;
        $c = !empty($context['currency_code']) ? $context['currency_code'] : $currencyCode;

        if (isset($prices["{$s}:{$g}:{$c}"])) {
            return $prices["{$s}:{$g}:{$c}"];
        }
        if (!$useDefault) {
            return null;
        }
        if ($s !== '*' && isset($prices["*:{$g}:{$c}"])) {
            return $prices["*:{$g}:{$c}"];
        }
        if ($g !== '*' && isset($prices["{$s}:*:{$c}"])) {
            return $prices["{$s}:*:{$c}"];
        }
        if ($c !== '*' && isset($prices["{$s}:{$g}:*"])) {
            return $prices["{$s}:{$g}:*"];
        }
        if ($s !== '*' && $g !== '*' && isset($prices["*:*:{$c}"])) {
            return $prices["*:*:{$c}"];
        }
        if ($s !== '*' && $c !== '*' && isset($prices["*:{$g}:*"])) {
            return $prices["*:{$g}:*"];
        }
        if ($g !== '*' && $c !== '*' && isset($prices["*:*:{$c}"])) {
            return $prices["*:*:{$c}"];
        }
        return isset($prices['*:*:*']) ? $prices['*:*:*'] : null;
    }

    /**
     * @param Sellvana_Catalog_Model_ProductPrice[] $priceModels
     * @param array $context
     * @param float $basePrice
     * @return Sellvana_Catalog_Model_ProductPrice
     */
    public function getCatalogPrice(array $priceModels, array $context = [], $basePrice = null)
    {
        $currency = !empty($context['currency_code']) ? $context['currency_code'] : null;

        /** @var static $priceModel */
        $priceModel = $this->getPriceModelByType($priceModels, 'base', $context);
        $price = $priceModel ? $priceModel->getPrice($basePrice, $currency) : 0;
        /** @var static $salePriceModel */
        $salePriceModel = $this->getPriceModelByType($priceModels, 'sale', $context);
        if ($salePriceModel && $salePriceModel->isValid()) {
            $salePrice = $salePriceModel->getPrice($price, $currency);
            if ($salePrice) {
                $price = min($price, $salePrice);
            }
        }

        return $price;
    }

    protected function _parsePriceField($value)
    {
        $value = strtolower($value);
        if (is_numeric($value)) {
            return [
                'operation' => '=$',
                'amount' => $value,
                'base_field' => null,
            ];
        } elseif (is_string($value) && preg_match('#^(base|sale|cost|msrp|map)([+\*-])([0-9.]+)(%?)$#', $value, $m)) {
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
