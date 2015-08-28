<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogFields_Frontend
 *
 * Uses:
 *
 *@property Sellvana_Sales_Model_Cart                  $Sellvana_Sales_Model_Cart
 * @property Sellvana_CatalogFields_Model_ProductVariant  $Sellvana_CatalogFields_Model_ProductVariant
 * @property Sellvana_CatalogFields_Model_ProductVarfield $Sellvana_CatalogFields_Model_ProductVarfield
 * @property Sellvana_Catalog_Model_InventorySku        $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_CatalogFields_Model_ProductField $Sellvana_CatalogFields_Model_ProductField
*/
class Sellvana_CatalogFields_Frontend extends BClass
{
    /**
     * Convert form data to cart item details
     *
     * @param $args
     *  - cart: (OPTIONAL) Sellvana_Sales_Model_Cart object
     *  - post: simple post data
     *      id: product_id
     *      qty:
     *      variant_select:
     *  - items: (SEQUENCE ARRAY, UPDATABLE) per item post data. if single, copied from `post`
     *    - id: product_id
     *      product: Sellvana_Catalog_Model_Product
     *      qty: item qty (optional, default: 1)
     *      details: resulting details data structure
     *          price: item price (optional, default: $product->getCatalogPrice() )
     *          product_sku: local catalog product unique id
     *          mfr_sku: manufacturer or wholesale sku (depends on variant)
     *          data:
     *              variants: (DEPRECATED)
     *                  product_id:
     *                  variant_qty:
     *                  variant_price:
     *                  field_values:
     *              display: (NEW)
     *                  - { label:"Color", text:"Blue" }
     *                  - { label:"Size, text:"Large" }
     *                  - { label:"Inscription", text:"For my love" }
     *                  - { type:img, src:preview.jpg }
     *                  - { label:"Extra", text:"Nice buttons" }
     *              variant: (NEW)
     *                  field_values: { color:blue, size:large }
     *              shopper_fields: (NEW, from Sellvana_ShopperFields)
     *                  - { label: "Label 1", value: "Val 1" }
     *      result:
     *          error:
     *
     * @return bool
     */
    public function onWorkflowCustomerAddsItemsCalcDetails($args)
    {
        $varfieldHlp = $this->Sellvana_CatalogFields_Model_ProductVarfield;
        $variantHlp = $this->Sellvana_CatalogFields_Model_ProductVariant;
        $utilHlp = $this->BUtil;

        foreach ($args['items'] as &$item) {
            if (empty($item['product'])) {
                BDebug::notice('Empty product id, no variants calculation');
                continue;
            }
            /** @var Sellvana_Catalog_Model_Product $p */
            $p = $item['product'];
            $pId = $p->id();
            /** @var Sellvana_CatalogFields_Model_ProductVariant[] $variants */
            $variants = $variantHlp->orm()->where('product_id', $pId)->find_many();
            if ($variants) {
                if (empty($item['variant_select'])) {
                    $item['error'] = $this->BLocale->_('Please specify the product variant');
                    $item['action'] = 'redirect_product';
                    continue;
                }
                $varValues = $item['variant_select'];
                /** @var Sellvana_CatalogFields_Model_ProductVarfield[][] $varfields */
                if (empty($varfields[$pId])) {
                    $varfields[$pId] = $varfieldHlp->orm('vf')
                        ->join('Sellvana_CatalogFields_Model_Field', ['f.id', '=', 'vf.field_id'], 'f')
                        ->select('vf.*')
                        ->select(['f.field_code', 'f.field_name', 'f.frontend_label'])
                        ->where('vf.product_id', $pId)
                        ->find_many_assoc('field_id');
                }
                $valArr = [];
                foreach ($varfields[$pId] as $vf) {
                    $code = $vf->get('field_code');
                    $valArr[$code] = $varValues[$code];
                }
                ksort($valArr);
                $variant = null;
                foreach ($variants as $v) {
                    $vData = $utilHlp->fromJson($v->get('field_values'));
                    ksort($vData);
                    if ($vData === $valArr) {
                        $variant = $v;
                        break;
                    }
                }
                if (!$variant) {
                    $item['error'] = $this->BLocale->_('Invalid variant');
                    continue;
                }

                $invSku = $variant->get('inventory_sku');
                if ($invSku) {
                    $item['details']['inventory_sku'] = $invSku;
                    $item['details']['signature']['inventory_sku'] = $invSku;
                }

                $item['details']['data']['variant_fields'] = $valArr;
                $item['details']['signature']['variant_fields'] = $valArr;

                if ($variant->get('variant_price') !== null) {
                    //TODO: implement dynamic variant prices
                    $item['details']['price'] = $variant->get('variant_price');
                }

                foreach ($varfields[$pId] as $vf) {
                    $item['details']['data']['display'][] = [
                        'label' => $vf->get('field_label') ?: ($vf->get('frontend_label' ?: $vf->get('field_name'))),
                        'value' => $varValues[$vf->get('field_code')],
                    ];
                }
            }
        }
        unset($item);

        return true;
    }

    /**
     * @param Sellvana_Catalog_Model_Product $product
     * @return array
     */
    public function customFieldsShowOnFrontend(Sellvana_Catalog_Model_Product $product)
    {
        $result = [];
        $fields = $this->Sellvana_CatalogFields_Model_ProductField->productFields($product);
        if ($fields) {
            foreach ($fields as $f) {
                if ($f->get('frontend_show')) {
                    $result[] = $f;
                }
            }
        }
        return $result;
    }
}
