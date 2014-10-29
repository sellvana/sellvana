<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @property string  field_values    (field1=value1&field2=value2&field3=value3)
 * @property string  variant_sku     (PROD_VAL1_VAL2_VAL3)
 * @property decimal variant_price
 * @property text    data_serialized
 *
 * Uses:
 * @property FCom_CustomField_Model_Field $FCom_CustomField_Model_Field
 * @property FCom_Catalog_Model_Product   $FCom_Catalog_Model_Product
 */
class FCom_CustomField_Model_ProductVariant extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_variant';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'related' => ['product_id' => 'FCom_Catalog_Model_Product.id'],
        'unique_key' => ['product_id', 'field_values'],  
    ];

    public function fetchProductVariantsData($product)
    {
        $fields = $product->getData('variants_fields');
        if (!$fields) {
            return ['fields' => [], 'variants' => [], 'variants_tree' => []];
        }
        $fieldIds = $this->BUtil->arrayToOptions($fields, 'id');
        $fieldModels = $this->FCom_CustomField_Model_Field->orm()->where_in('id', $fieldIds)->find_many_assoc();
        foreach ($fields as $k => $f) {
            $m = $fieldModels[$f['id']];
            $fields[$k]['frontend_label'] = $m->get('frontend_label') ? $m->get('frontend_label') : $m->get('name');
        }
        $varModels = $this->orm()->where('product_id', $product->id())->find_many();
        $variants = [];
        $fieldValues = [];
        foreach ($varModels as $m) {
            $vr = $m->as_array();
            unset($vr['data_serialized']);
            $vr['field_values'] = $this->BUtil->fromJson($vr['field_values']);
            $imgIds = $m->getData('variant_file_id');
            $vr['img_ids'] = $imgIds ? explode(',', $imgIds) : [];
            $vr['variant_sku'] = ($vr['variant_sku'] === '') ? $product->product_sku : $vr['variant_sku'];
            $price = ($vr['variant_price'] > 0) ? $vr['variant_price'] : $product->base_price;
            $vr['variant_price'] = $this->BLocale->currency($price);
            $vrKeyArr = [];
            foreach ($fields as $f) {
                $k = $f['field_code'];
                $v = $vr['field_values'][$k];
                $fieldValues[$k][$v] = $v;
                $vrKeyArr[] = $v;
            }
            $vrKey = join('|', $vrKeyArr);
            $variants[$vrKey] = $vr;
        }
        foreach ($fields as $k => $field) {
            $fields[$k]['options'] = $fieldValues[$field['field_code']];
        }
        $varTree = $this->_buildVariantTree($fields, $variants);
        return ['fields' => $fields, 'variants' => $variants, 'variants_tree' => $varTree];
    }

    protected function _buildVariantTree($fields, $variants, $path = [])
    {
        $field = array_shift($fields);
        $values = [];
        foreach ($variants as $vr) {
            $f = $field['field_code'];
            $v = $vr['field_values'][$f];
            $values[$v] = $v;
        }
        $children = [];
        foreach ($values as $v) {
            $childPath = $path + [$f => $v];
            $childKey = join('|', $childPath);
            if (!empty($variants[$childKey])) { // last level
                $children[$v] = $variants[$childKey];
                continue;
            }
            $childVariants = [];
            foreach ($variants as $vrKey => $vr) {
                if (strpos($vrKey, $childKey . '|') === 0) {
                    $childVariants[$vrKey] = $vr;
                }
            }
            if ($childVariants) {
                $children[$v] = $this->_buildVariantTree($fields, $childVariants, $childPath);
            }
        }
        return $children;
    }

    public function findByProductFieldValues($product, $fieldValues)
    {
        if (is_numeric($product)) {
            $product = $this->FCom_Catalog_Model_Product->load($product);
        }
        $valArr = [];
        $fields = $product->getData('variants_fields');
        foreach ($fields as $f) {
            $valArr[$f['field_code']] = $fieldValues[$f['field_code']];
        }
        ksort($valArr);
        $valJson = $this->BUtil->toJson($valArr);
        $variant = $this->loadWhere(['product_id' => $product->id(), 'field_values' => $valJson]);
        return $variant;
    }

    public function checkEmptyVariant($product)
    {
        return $this->load($product, 'product_id');
    }
}
