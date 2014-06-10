<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CustomField_Frontend extends BClass
{
    public function onCheckoutCartAddValidate($args)
    {
        $p = $args['product'];
        if (!$p->getData('variants_fields')) {
            return true;
        }
        if (empty($args['post']['variant_select'])) {
            $args['result']['error'] = $this->BLocale->_('Please specify the product variant');
            return false;
        }
        $varValues = $args['post']['variant_select'];
        $variant = $this->FCom_CustomField_Model_ProductVariant->findByProductFieldValues($p, $varValues);
        if (!$variant) {
            $args['result']['error'] = $this->BLocale->_('Invalid variant');
            return false;
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
        $args['options']['data']['variants'] = $variant->as_array();
        return true;
    }
}