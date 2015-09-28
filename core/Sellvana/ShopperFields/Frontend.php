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
        $result = [];
        foreach ($frontendFields as $field) {
            if (empty($field['option_serialized'])) {
                $result[$field['name']] = $field;
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
            $result[$field['name']] = $field;
        }

        uasort($result, function ($a, $b) {
            $pa = isset($a['position']) ? (int)$a['position'] : 0;
            $pb = isset($b['position']) ? (int)$b['position'] : 0;
            return $pa < $pb ? -1 : ($pa > $pb ? 1 : 0);
        });

        return $result;
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
#var_dump(__METHOD__, $item['shopper'], $frontendFields);
            foreach ($item['shopper'] as $key => $value) {
                if (!isset($value['val']) || $value['val'] === '') {
                    unset($item['shopper'][$key]);
                    continue;
                }
                if (empty($frontendFields[$key])) {
                    unset($item['shopper'][$key]);
                    continue;
                }
                $field = $frontendFields[$key];

                $val = $value['val'];
                if (!empty($field['options'])) {
                    if (empty($field['options'][$val])) {
                        unset($item['shopper'][$key]);
                        continue;
                    } else {
                        $option = $field['options'][$val];
                    }
                }
                if ($field['input_type'] === 'checkbox') {
                    $val = $item['shopper'][$key]['val'] = 'Yes';
                }
                if (empty($field['qty_min'])) {
                    $qty = 1;
                } else {
                    $qty = !empty($value['qty']) ? (int)$value['qty'] : $field['qty_min'];
                    $qty = max($qty, $field['qty_min']);
                    if (!empty($field['qty_max'])) {
                        $qty = min($qty, $field['qty_max']);
                    }
                }
                if (!empty($option['sku'])) {
                    $item['details']['data']['inventory_skus'][] = [
                        'sku' => $option['sku'],
                        'qty' => $qty,
                    ];
                }
                $item['details']['signature']['shopper_fields'][$key] = [$val, $qty];
                $item['details']['data']['shopper_fields'][$key] = ['val' => $val, 'qty' => $qty];
                $item['details']['data']['display'][] = [
                    'label' => $field['label'],
                    'value' => $val . ($qty > 1 ? ' x' . $qty : ''),
                ];
            }
        }
        unset($item);

       # echo "<pre>"; print_r($args); exit;
        return true;
    }
}