<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ShopperFields_Frontend extends BClass
{
    /**
     * @param FCom_Catalog_Model_Product $product
     * @return mixed
     */
    public function getProductFrontendFields(FCom_Catalog_Model_Product $product)
    {
        $frontendFields = $product->getData('frontend_fields');
        if ($frontendFields) {
            usort($frontendFields, function ($a, $b) {
                if ($a['position'] == $b['position']) {
                    return 0;
                }
                return ($a['position'] < $b['position'])? -1: 1;
            });
        }
        return $frontendFields;
    }

    public function onWorkflowCustomerAddsItemsCalcDetails($args)
    {
        foreach ($args['items'] as &$item) {
            if (empty($item['shopper'])) {
                continue;
            }
            foreach ($item['shopper'] as $key => $value) {
                if (!isset($value['val']) || $value['val'] == '') {
                    unset($item['shopper'][$key]);
                    continue;
                }
                if ($value['val'] === 'checkbox') {
                    $item['shopper'][$key]['val'] = null;
                }
                $item['details']['signature']['shopper_fields'][$key] = $value['val'];
                $item['details']['shopper_fields'][$key] = $value['val'];
                $item['details']['data']['display'][] = ['label' => $key, 'value' => $value['val']];
            }
        }
        unset($item);
        return true;
    }
}