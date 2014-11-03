<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_CustomField_Frontend
 *
 * @property FCom_CustomField_Model_ProductVariant $FCom_CustomField_Model_ProductVariant
 */
class FCom_CustomField_Frontend extends BClass
{
    public function onCheckoutCartAddValidate($args)
    {
        $p = $args['product'];
        $defaultVariant = [
            'product_id' => $args['post']['id'],
            'variant_qty' => $args['options']['qty'],
            'variant_price' => $args['options']['price'],
            'field_values' => ""
        ];
        if ($p->getData('variants_fields')) {
            $varValues = $args['post']['variant_select'];
            /** @var FCom_CustomField_Model_ProductVariant $variant */
            $variant = $this->FCom_CustomField_Model_ProductVariant->findByProductFieldValues($p, $varValues);

            if ($this->FCom_CustomField_Model_ProductVariant->checkEmptyVariant($args['post']['id'])) {
                if (empty($args['post']['variant_select'])) {
                    $args['result']['error'] = $this->BLocale->_('Please specify the product variant');
                    return false;
                } else {
                    if (!$variant) {
                        $args['result']['error'] = $this->BLocale->_('Invalid variant');
                        return false;
                    }
                }
                if (!$variant->variant_qty) { //TODO: allow empty qty
                    $args['result']['error'] = $this->BLocale->_('The variant is out of stock');
                    return false;
                }
                if ($variant->variant_qty < $args['options']['qty']) {
                    $args['result']['error'] = $this->BLocale->_('The variant currently has only %s items in stock', $variant->variant_qty);
                    return false;
                }

                if ($variant->variant_price > 0) { //TODO: allow free variants
                    $args['options']['price'] = $variant->variant_price;
                }
                $defaultVariant = $variant->as_array();
            } else {
                //TODO: validate when product empty variant
            }
        }
        $args['options']['data']['variants'] = $defaultVariant;
        return true;
    }
}