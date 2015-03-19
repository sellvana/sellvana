<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomField_Frontend
 *
 * Uses:
 * @property Sellvana_Sales_Model_Cart                  $Sellvana_Sales_Model_Cart
 * @property Sellvana_CustomField_Model_ProductVariant  $Sellvana_CustomField_Model_ProductVariant
 * @property Sellvana_CustomField_Model_ProductVarfield $Sellvana_CustomField_Model_ProductVarfield
 * @property Sellvana_Catalog_Model_InventorySku        $Sellvana_Catalog_Model_InventorySku
 */
class Sellvana_CustomField_Frontend extends BClass
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
        $varfieldHlp = $this->Sellvana_CustomField_Model_ProductVarfield;
        $variantHlp = $this->Sellvana_CustomField_Model_ProductVariant;

        foreach ($args['items'] as &$item) {
            if (empty($item['product'])) {
                BDebug::notice('Empty product id, no variants calculation');
                continue;
            }
            $p = $item['product'];
            $variants = $variantHlp->orm()->where('product_id', $p->id())->find_many();
            if ($variants) {
                if (empty($item['variant_select'])) {
                    $item['error'] = $this->BLocale->_('Please specify the product variant');
                    $item['action'] = 'redirect_product';
                    continue;
                }
                $varValues = $item['variant_select'];
                $varfields = $varfieldHlp->orm('vf')
                    ->join('Sellvana_CustomField_Model_Field', ['f.id', '=', 'vf.field_id'], 'f')
                    ->select('vf.*')
                    ->select(['f.field_code', 'f.field_name', 'f.frontend_label'])
                    ->where('vf.product_id', $p->id())
                    ->find_many_assoc('field_id');
                $valArr = [];
                foreach ($varfields as $vf) {
                    $code = $vf->get('field_code');
                    $valArr[$code] = $varValues[$code];
                }
                ksort($valArr);
                $valJson = $this->BUtil->toJson($valArr);
                $variant = null;
                foreach ($variants as $v) {
                    if ($v->get('field_values') === $valJson) {
                        $variant = $v;
                        break;
                    }
                }
                if (!$variant) {
                    $item['error'] = $this->BLocale->_('Invalid variant');
                    continue;
                }
                $varArr = $variant->as_array();
                $invSku = $varArr['inventory_sku'] !== null ? $varArr['inventory_sku'] : $p->get('inventory_sku');
                $invModel = $this->Sellvana_Catalog_Model_InventorySku->load($invSku, 'inventory_sku');

                if ($varArr['inventory_sku'] !== null && !$invModel) { // TODO: only for debugging during development
                    $invModel = $this->Sellvana_Catalog_Model_InventorySku->load($p->get('inventory_sku'), 'inventory_sku');
                }

                if ($p->get('manage_inventory') && $invModel->get('manage_inventory')) {
                    $availQty = $invModel->getQtyAvailable();
                    if (!$availQty) {
                        $item['error'] = $this->BLocale->_('The variant is out of stock');
                        continue;
                    }
                    if ($availQty < $item['qty']) {
                        $item['error'] = $this->BLocale->_('The variant currently has only %s items in stock', $availQty);
                        continue;
                    }
                }

                $item['details']['inventory_id'] = $invModel->id();
                $item['details']['inventory_sku'] = $invModel->get('inventory_sku');

                $item['details']['signature']['inventory_sku'] = $item['details']['inventory_sku'];
                $item['details']['signature']['variant_fields'] = $valArr;

                if ($varArr['variant_price'] !== null) {
                    $item['details']['price'] = $varArr['variant_price'];
                }

                $item['details']['data']['variant_fields'] = $valArr;
                foreach ($varfields as $vf) {
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
}
