<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Catalog_Model_ProductPrice
 *
 * @property int $id
 * @property int $product_id
 * @property int $group_id
 * @property float $base_price
 * @property float $sale_price
 * @property int $qty
 */
class FCom_Catalog_Model_ProductPrice
    extends FCom_Core_Model_Abstract
{
    protected static $_table = "fcom_product_price";
    protected static $_origClass = __CLASS__;

    /**
     * @param FCom_Catalog_Model_Product $product
     * @return FCom_Catalog_Model_ProductPrice[]
     * @throws BException
     */
    public function getProductTiers($product)
    {
        $tiers = $this->orm('tp')->where('product_id', $product->id())->find_many();
        $salePrice = (float)$product->get('sale_price');
        $basePrice = (float)$product->get('base_price');
        $price = $salePrice ? $salePrice : $basePrice;
        #$this->BDebug->dump($tiers);
        #var_dump($salePrice, $basePrice, $price);
        foreach ($tiers as $tier) {
            $tier->set('save_percent', ceil((1 - $tier->get('price') / $price) * 100));
        }
        return $tiers ? $this->BDb->many_as_array($tiers) : [];
    }
}
