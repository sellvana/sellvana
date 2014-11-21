<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_CustomField_Frontend
 *
 * Uses:
 * @property FCom_Sales_Model_Cart                  $FCom_Sales_Model_Cart
 * @property FCom_CustomField_Model_ProductVariant  $FCom_CustomField_Model_ProductVariant
 * @property FCom_CustomField_Model_ProductVarfield $FCom_CustomField_Model_ProductVarfield
 */
class FCom_CustomField_Frontend extends BClass
{
    /**
     * Convert form data to cart item details
     *
     * @param $args
     *  - cart: (OPTIONAL) FCom_Sales_Model_Cart object
     *  - post: simple post data
     *      id: product_id
     *      qty:
     *      variant_select:
     *  - items: (SEQUENCE ARRAY, UPDATABLE) per item post data. if single, copied from `post`
     *    - id: product_id
     *      product: FCom_Catalog_Model_Product
     *      qty: item qty (optional, default: 1)
     *      details: resulting details data structure
     *          price: item price (optional, default: $product->getPrice() )
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
     *              frontend_fields: (NEW)
     *                  - { label: "Label 1", val: "Val 1" }
     *      result:
     *          error:
     *
     * @return bool
     */
    public function onWorkflowCustomerAddsItemsCalcDetails($args)
    {
        $post = $args['post'];
        // TODO: Use for child items for bundles
        $cart = !empty($args['cart']) ? $args['cart'] : $this->FCom_Sales_Model_Cart->sessionCart();

        $varfieldHlp = $this->FCom_CustomField_Model_ProductVarfield;
        $variantHlp = $this->FCom_CustomField_Model_ProductVariant;

        foreach ($args['items'] as &$item) {
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
                    ->join('FCom_CustomField_Model_Field', ['f.id', '=', 'vf.field_id'], 'f')
                    ->select('vf.*')
                    ->select('f.field_code')
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
                $availQty = $variant->get('variant_qty');
                if (!$availQty) { //TODO: allow empty qty
                    $item['error'] = $this->BLocale->_('The variant is out of stock');
                    continue;
                }
                if ($availQty < $item['qty']) {
                    $item['error'] = $this->BLocale->_('The variant currently has only %s items in stock', $variant->variant_qty);
                    continue;
                }

                if ($variant->get('variant_price') > 0) { //TODO: allow free variants
                    $args['options']['price'] = $variant->variant_price;
                }
                $item['details']['variant'] = $variant->as_array();
            }
            //$args['options']['data']['variants'] = $defaultVariant;

            if (isset($args['post']['shopper'])) {
                $options['shopper'] = $args['post']['shopper'];
                foreach ($options['shopper'] as $key => $value) {
                    if (!isset($value['val']) || $value['val'] == '') {
                        unset($options['shopper'][$key]);
                    }
                    if ($value['val'] == 'checkbox') {
                        unset($options['shopper'][$key]['val']);
                    }
                }
            }

            // FROM Cart::addProduct()
            if (isset($params['data'])) {
                $variants = $item->getData('variants');
                $flag = true;
                $params['data']['variants']['field_values'] = $this->BUtil->fromJson($params['data']['variants']['field_values']);
                if (null !== $variants) {
                    foreach ($variants as &$arr) {
                        if (in_array($params['data']['variants']['field_values'], $arr)) {
                            $flag = false;
                            $arr['variant_qty'] = $arr['variant_qty'] + $params['qty'];
                            if (isset($params['shopper'])) {
                                $arr['shopper'] = $params['shopper'];
                            }

                        }
                    }
                }
                if ($flag) {
                    if (!empty($params['data']['variants'])) {
                        $params['data']['variants']['variant_qty'] = $params['qty'];
                        $variants = (null !== $variants) ? $variants : [];
                        if (isset($params['shopper'])) {
                            $params['data']['variants']['shopper'] = $params['shopper'];
                        }
                        array_push($variants, $params['data']['variants']);
                    }
                }
                $item->setData('variants', $variants);
            }
        }
        unset($item);

        return true;
    }
}
