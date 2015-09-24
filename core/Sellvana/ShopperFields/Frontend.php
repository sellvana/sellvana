<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_ShopperFields_Frontend
 *
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 */
class Sellvana_ShopperFields_Frontend extends BClass
{

    /**
     * @param Sellvana_Catalog_Model_Product $product
     * @return mixed
     */
    public function getProductFrontendFields(Sellvana_Catalog_Model_Product $product)
    {
        $frontendFields = $product->getData('frontend_fields');
        if (!$frontendFields) {
            return [];
        }
        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        foreach ($frontendFields as &$field) {
            if (empty($field['option_serialized'])) {
                continue;
            }
            $options = json_decode($field['option_serialized'], true);
            foreach ($options as $optionLabel => &$option) {
                $option['label'] = $optionLabel;
                if (!empty($option['prices'])) {
                    $priceModels = [];
                    foreach ($option['prices'] as $p) {
                        $priceModel = $priceHlp->create([
                            'price_type' => $p['price_type'],
                            'amount' => $p['amount'],
                            'operation' => $p['operation'],
                            'qty' => $p['qty'],
                            'valid_from' => !empty($p['valid_from']) ? $p['valid_from'] : null,
                            'valid_to' => !empty($p['valid_to']) ? $p['valid_to'] : null,
                        ])->setReadOnly(true);

                        $key = "{$p['site_id']}:{$p['customer_group_id']}:{$p['currency_code']}";
                        if ($p['price_type'] === 'tier') {
                            $priceModels[0][$p['price_type']][$key][$p['qty']] = $priceModel;
                        } else {
                            $priceModels[0][$p['price_type']][$key] = $priceModel;
                        }
                    }
                    $option['price'] = $priceHlp->getCatalogPrice($priceModels);
                }
            }
            unset($option);
            uasort($options, function ($a, $b) {
                $pa = isset($a['position']) ? (int)$a['position'] : 999;
                $pb = isset($b['position']) ? (int)$b['position'] : 999;
                return $pa < $pb ? -1 : ($pa > $pb ? 1 : 0);
            });
            $field['options'] = $options;
        }
        unset($field);

        usort($frontendFields, function ($a, $b) {
            $pa = isset($a['position']) ? (int)$a['position'] : 0;
            $pb = isset($b['position']) ? (int)$b['position'] : 0;
            return $pa < $pb ? -1 : ($pa > $pb ? 1 : 0);
        });

        return $frontendFields;
    }

    public function onWorkflowCustomerAddsItemsCalcDetails($args)
    {
        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        foreach ($args['items'] as $i => &$item) {
            if (empty($item['shopper'])) {
                continue;
            }
            $frontendFields = $this->getProductFrontendFields($item['product']);
            if (!$frontendFields) {
                unset($args['items'][$i]);
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
                $item['details']['data']['shopper_fields'][$key] = $value['val'];
                $item['details']['data']['display'][] = ['label' => $value['label'], 'value' => $value['val']];
            }
        }
        unset($item);
       # echo "<pre>"; print_r($args); exit;
        return true;
    }
}