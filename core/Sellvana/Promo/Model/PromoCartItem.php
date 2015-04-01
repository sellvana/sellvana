<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Model_PromoCartItem
 *
 * @property Sellvana_Promo_Main $Sellvana_Promo_Main
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_Promo_Model_PromoCartItem extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_cart_item';

    const TYPE_MATCHED = 1,
        TYPE_ADDED = 2;

    public function getItemRelatedPromos(Sellvana_Sales_Model_Cart_Item $item)
    {
        static $promoCartItemCache = [];
        static $productCache = [];
        //static $promosCache = [];

        $cartId = $item->get('cart_id');

        if (empty($promoCartItemCache[$cartId])) {
            $pcis = $this->orm('pci')->where('pci.cart_id', $item->get('cart_id'))
                ->join('Sellvana_Promo_Model_PromoCart', ['pc.id', '=', 'pci.promo_cart_id'], 'pc')
                ->join('Sellvana_Promo_Model_Promo', ['p.id', '=', 'pc.promo_id'], 'p')
                ->select('pc.*')
                ->select(['pci.cart_item_id', 'pci.item_type'])
                ->select(['p.customer_label', 'p.customer_details'])
                ->find_many();
            foreach ($pcis as $pci) {
                $promoCartItemCache[$cartId][$pci->get('cart_item_id')][$pci->get('promo_id')] = $pci;
            }
        }

        if (empty($promoCartItemCache[$cartId][$item->id()])) {
            return [];
        }

        $pcis = $promoCartItemCache[$cartId][$item->id()];
        $skus = [];
        foreach ($pcis as $pci) {
            $freeItems = $pci->getData('free_items');
            if (!empty($freeItems['sku'])) {
                $skus = array_merge($skus, $freeItems['sku']);
            }
        }
        if (!empty($skus)) {
            $skus = array_diff(array_unique($skus), array_keys($productCache));
            if (!empty($skus)) {
                //TODO: load products only for unused promotions!
                $newProducts = $this->Sellvana_Catalog_Model_Product->orm()
                    ->where_in('product_sku', array_unique($skus))->find_many_assoc('product_sku');
                $productCache = array_merge($productCache, $newProducts);
            }
            foreach ($pcis as $pci) {
                $freeItems = $pci->getData('free_items');
                if (!empty($freeItems['sku'])) {
                    $pciProducts = [];
                    foreach ($freeItems['sku'] as $sku) {
                        $pciProducts[$sku] = $productCache[$sku];
                    }
                    $pci->set('products', $pciProducts);
                }
            }
        }

        return $pcis;
    }

}