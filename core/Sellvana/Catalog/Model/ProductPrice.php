<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Model_ProductPrice
 *
 * @property int $id
 * @property int $product_id
 * @property int $group_id
 * @property float $base_price
 * @property float $sale_price
 * @property int $qty
 */
class Sellvana_Catalog_Model_ProductPrice
    extends FCom_Core_Model_Abstract
{
    protected static $_table = "fcom_product_price";
    protected static $_origClass = __CLASS__;

    const TYPE_BASE = "base",
        TYPE_MAP = "map",
        TYPE_MSRP = "msrp",
        TYPE_SALE = "sale",
        TYPE_TIER = "tier",
        TYPE_COST = "cost",
        TYPE_PROMO = "promo";

    protected static $_fieldOptions = [
        'price_types' => [
            self::TYPE_BASE => "Base Price",
            self::TYPE_MAP => "MAP",
            self::TYPE_MSRP => "MSRP",
            self::TYPE_SALE => "Sale Price",
            self::TYPE_TIER => "Tier Price",
            self::TYPE_COST => "Cost Price",
            self::TYPE_PROMO => "Promo Price"
        ],
        'editable_prices' => [
            'base', 'map', 'msrp', 'sale', 'tier'
        ]
    ];

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
        $orm    = $this->orm('tp')
            ->where('product_id', $product->id());
        if($date){
            $orm->where_complex([
                'OR' => [
                    'valid_from IS NULL AND valid_to IS NULL',
                    ['(? BETWEEN valid_from AND valid_to)', $date]
                ]
            ]);
        }
        if(!empty($qty)){
            $orm->where(['qty <= ?', $qty]); // tier prices up to provided qty
        }
        if(!empty($customer_group_id)){
            $orm->where('customer_group_id', $customer_group_id);
        }
        if(!empty($site_id)){
            $orm->where('site_id', $site_id);
        }
        if(!empty($currency_code)){
            $orm->where('currency_code', $currency_code);
        }


        $prices = $orm->find_many();
        if (!empty($prices)) {
            $salePrice = (float) $product->get('sale_price');
            $basePrice = (float) $product->get('base_price');
            $price     = $salePrice? $salePrice: $basePrice;
            #$this->BDebug->dump($tiers);
            #var_dump($salePrice, $basePrice, $price);
            foreach ($prices as $p) {
                $p->set('save_percent', ceil((1 - $p->get('price') / $price) * 100));
            }
        }
        return $prices ? $this->BDb->many_as_array($prices) : [];
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
    public function getPrice($product, $price_type, $qty = 1, $customerGroupId = null, $siteId = null, $currencyCode = null)
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
    public function getVariantPrice($variant_id, $type = self::TYPE_BASE)
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
    public function saveVariantPrice($price, $variant_id, $product_id, $type = self::TYPE_BASE)
    {
        $priceModel = $this->getVariantPrice($variant_id);
        if(!$priceModel){
            $priceModel = $this->create();
        }

        $priceModel->set([
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'price'      => (float) $price,
            'price_type' => $type
        ])->save();
    }
}
