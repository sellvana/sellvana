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

    /**
     * @param Sellvana_Catalog_Model_Product $product
     * @return Sellvana_Catalog_Model_ProductPrice[]
     * @throws BException
     */
    public function getProductPrices($product)
    {
        $prices = $this->orm('tp')->where('product_id', $product->id())->find_many();
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
        ]);

        return $price;
    }
}
