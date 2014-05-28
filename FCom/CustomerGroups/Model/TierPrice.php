<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Model_TierPrice
    extends FCom_Core_Model_Abstract
{
    protected static $_table = "fcom_tier_prices";
    protected static $_origClass = __CLASS__;

    /**
     * @param bool  $new
     * @param array $args
     * @return FCom_CustomerGroups_Model_TierPrice
     */
    public static function i($new = false, array $args = [])
    {
        return parent::i($new, $args); // auto completion helper
    }

    public static function getProductTiers($product)
    {
        $tiers = static::orm('tp')->where('product_id', $product->id())->find_many();
        $salePrice = (float)$product->get('sale_price');
        $basePrice = (float)$product->get('base_price');
        $price = $salePrice ? $salePrice : $basePrice;
        #BDebug::dump($tiers);
        #var_dump($salePrice, $basePrice, $price);
        foreach ($tiers as $tier) {
            $tier->set('save_percent', ceil((1 - $tier->get('sale_price') / $price) * 100));
        }
        return $tiers ? BDb::many_as_array($tiers) : [];
    }
}
