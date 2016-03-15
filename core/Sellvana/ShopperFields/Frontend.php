<?php

/**
 * Class Sellvana_ShopperFields_Frontend
 *
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
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
        $result = [];
        $fetchSkus = [];
        foreach ($frontendFields as $field) {
            if (empty($field['option_serialized'])) {
                $result[$field['name']] = $field;
                continue;
            }
            $options = json_decode($field['option_serialized'], true);
            foreach ($options as $optionLabel => &$option) {
                $option['label'] = $optionLabel;
                if (!empty($option['prices'])) {
                    $option['price'] = $this->_getOptionPrice($option['prices']);
                }
                if (!empty($option['sku'])) {
                    $fetchSkus[$option['sku']][] = ['f' => $field['name'], 'o' => $optionLabel];
                }
            }
            unset($option);
            uasort($options, [$this, '_sortByPosition']);
            $field['options'] = $options;
            $result[$field['name']] = $field;
        }
        uasort($result, [$this, '_sortByPosition']);

        if ($fetchSkus) {
            $skuModels = $this->Sellvana_Catalog_Model_InventorySku->orm('i')
                ->where_in('inventory_sku', array_keys($fetchSkus))->find_many_assoc('inventory_sku');
            foreach ($fetchSkus as $sku => $skuValues) {
                if (empty($skuModels[$sku])) {
                    continue;
                }
                /** @var Sellvana_Catalog_Model_InventorySku $m */
                $m = $skuModels[$sku];
                foreach ($skuValues as $s) {
                    $field = &$result[$s['f']];
                    $option = &$field['options'][$s['o']];
                    #$option['inventory'] = $m;
                    $option['available'] = $m->canOrder(!empty($field['qty_min']) ? $field['qty_min'] : 1);
                    if (!$option['available']) {
                        $option['qty_max'] = 0;
                    } elseif (!empty($field['qty_max'])) {
                        $option['qty_max'] = min($field['qty_max'], $m->getQtyAvailable());
                    } else {
                        $option['qty_max'] = $m->getQtyAvailable();
                    }
                }
            }
        }

        return $result;
    }

    protected function _sortByPosition($a, $b)
    {
        $pa = isset($a['position']) ? (int)$a['position'] : 999;
        $pb = isset($b['position']) ? (int)$b['position'] : 999;
        return $pa < $pb ? -1 : ($pa > $pb ? 1 : 0);
    }

    protected function _getOptionPrice($prices)
    {
        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        $priceModels = [];
        foreach ($prices as $p) {
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
        return $priceHlp->getCatalogPrice($priceModels);
    }


    public function onWorkflowCustomerAddsItemsCalcDetails($args)
    {
        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        $invHlp = $this->Sellvana_Catalog_Model_InventorySku;

        $skus = [];

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
            foreach ($item['shopper'] as $fieldKey => $value) {
                if (!isset($value['val']) || $value['val'] === '') {
                    unset($item['shopper'][$fieldKey]);
                    continue;
                }
                if (empty($frontendFields[$fieldKey])) {
                    unset($item['shopper'][$fieldKey]);
                    continue;
                }
                $field = $frontendFields[$fieldKey];

                $val = $value['val'];
                if (!empty($field['options'])) {
                    if (empty($field['options'][$val])) {
                        unset($item['shopper'][$fieldKey]);
                        continue;
                    } else {
                        $option = $field['options'][$val];
                    }
                }
                if ($field['input_type'] === 'checkbox') {
                    $val = $item['shopper'][$fieldKey]['val'] = 'Yes';
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
                    $skuData = [
                        'type' => 'shopper_field',
                        'item_idx' => $i,
                        'field' => $fieldKey,
                        'value' => $val,
                        'sku' => $option['sku'],
                        'qty' => $qty,
                    ];
                    $skus[$option['sku']][] = $skuData;
                    $item['details']['data']['inventory_skus'][] = $skuData;
                }
                $item['details']['signature']['shopper_fields'][$fieldKey] = [$val, $qty];
                $item['details']['data']['shopper_fields'][$fieldKey] = ['val' => $val, 'qty' => $qty];
                $item['details']['data']['display'][] = [
                    'label' => $field['label'],
                    'value' => $val . ($qty > 1 ? ' x' . $qty : ''),
                ];
            }
        }
        unset($item);

        if ($skus) {
            /** @var Sellvana_Catalog_Model_InventorySku[] $skuModels */
            $skuModels = $invHlp->orm('i')->where_in('inventory_sku', array_keys($skus))->find_many_assoc('inventory_sku');
            foreach ($skuModels as $skuModel) {
                if (!$skuModel->canOrder()) {
                    $sku = $skuModel->get('inventory_sku');
                    foreach ($skus[$sku] as $s) {
                        $args['items'][$s['item_idx']]['error'] = $this->_('Bundled SKU %s out of stock', $sku);
                    }
                }
            }
        }

       # echo "<pre>"; print_r($args); exit;
        return true;
    }
}