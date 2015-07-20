<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogFields_Model_ProductVariant
 *
 * @property int $id
 * @property int $product_id
 * @property string $field_values (field1=value1&field2=value2&field3=value3)
 * @property string $product_sku (PROD_VAL1_VAL2_VAL3)
 * @property string $inventory_sku (PROD_VAL1_VAL2_VAL3)
 * @property float $variant_price
 * @property string $data_serialized
 * @property int $variant_qty
 *
 * DI
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_CatalogFields_Model_Field $Sellvana_CatalogFields_Model_Field
 * @property Sellvana_CatalogFields_Model_ProductVarfield $Sellvana_CatalogFields_Model_ProductVarfield
 * @property Sellvana_CatalogFields_Model_FieldOption $Sellvana_CatalogFields_Model_FieldOption
 * @property Sellvana_CatalogFields_Model_ProductVariantImage $Sellvana_CatalogFields_Model_ProductVariantImage
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_CatalogFields_Model_ProductVariantField $Sellvana_CatalogFields_Model_ProductVariantField
 */
class Sellvana_CatalogFields_Model_ProductVariant extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_variant';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'related' => ['product_id' => 'Sellvana_Catalog_Model_Product.id'],
        'unique_key' => ['product_id', 'field_values'],
    ];

    /**
     * @param Sellvana_Catalog_Model_Product $product
     * @return array
     */
    public function fetchProductVariantsData($product)
    {
        $pId = $product->id();
        $varfieldHlp = $this->Sellvana_CatalogFields_Model_ProductVarfield;
        $varfieldModels = $varfieldHlp->orm('vf')
            ->join('Sellvana_CatalogFields_Model_Field', ['f.id', '=', 'vf.field_id'], 'f')
            ->select(['vf.field_id', 'vf.field_label', 'vf.position'])
            ->select('f.*')
            ->where('vf.product_id', $pId)
            ->order_by_asc('vf.position')
            ->find_many_assoc('field_id');

        if (!$varfieldModels) {
            return ['fields' => [], 'variants' => [], 'variants_tree' => []];
        }

        $fields = $this->BDb->many_as_array($varfieldModels);

        /** @var Sellvana_CatalogFields_Model_ProductVarfield[] $varModels */
        $varModels = $this->orm()->where('product_id', $pId)->find_many_assoc();

        $invSkus = [$product->get('inventory_sku')];
        foreach ($varModels as $vm) {
            if ($vm->get('inventory_sku')) {
                $invSkus[] = $vm->get('inventory_sku');
            }
        }
        array_unique($invSkus);
        $invModels = $this->Sellvana_Catalog_Model_InventorySku->orm()->where_in('inventory_sku', $invSkus)->find_many_assoc('inventory_sku');

        $varImageHlp = $this->Sellvana_CatalogFields_Model_ProductVariantImage;
        $varImageModels = $varImageHlp->orm()->where('product_id', $pId)->find_many();
        $images = [];
        foreach ($varImageModels as $m) {
            $images[$m->get('variant_id')][] = $m->get('file_id');
        }

        foreach ($fields as &$f) {
            $f['frontend_label'] = $f['field_label'] ? $f['field_label'] : ($f['frontend_label'] ? $f['frontend_label'] : $f['field_name']);
        }
        unset($f);

        $variants = [];
        $fieldValues = [];
        foreach ($varModels as $m) {
            $vr = $m->as_array();
            unset($vr['data_serialized']);
            $vr['field_values'] = $this->BUtil->fromJson($vr['field_values']);
            $vr['img_ids'] = !empty($images[$vr['id']]) ? $images[$vr['id']] : [];
            $vr['product_sku'] = ($vr['product_sku'] === '') ? $product->get('product_sku') : $vr['product_sku'];
            $vr['inventory_sku'] = ($vr['inventory_sku'] === '') ? $product->get('inventory_sku') : $vr['inventory_sku'];
            $price = ($vr['variant_price'] > 0) ? $vr['variant_price'] : $product->get('base_price');
            $vr['variant_price'] = $this->BLocale->currency($price);
            $vr['variant_qty'] = !empty($invModels[$vr['inventory_sku']]) ? $invModels[$vr['inventory_sku']]->get('qty_in_stock') : 0;
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
        foreach ($fields as &$f) {
            $f['options'] = $fieldValues[$f['field_code']];
        }
        unset($f);
        $varTree = $this->_buildVariantTree($fields, $variants);
        return ['fields' => array_values($fields), 'variants' => $variants, 'variants_tree' => $varTree];
    }

    /**
     * @param array $fields
     * @param array $variants
     * @param array $path
     * @return array
     */
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

    /**
     * @param Sellvana_Catalog_Model_Product $product
     * @param array $fieldValues
     * @return self[]
     * @throws BException
     */
    public function findByProductFieldValues($product, $fieldValues)
    {
        if (is_numeric($product)) {
            $product = $this->Sellvana_Catalog_Model_Product->load($product);
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

    public function onAfterLoad()
    {
        $priceModel = $this->Sellvana_Catalog_Model_ProductPrice->getVariantPriceModel($this->id());
        if($priceModel){
            $this->set('variant_price', $priceModel->getPrice());
        }
        parent::onAfterLoad();
    }

    public function onAfterSave()
    {
        $price = $this->get('variant_price');
        if($price){
            $this->Sellvana_Catalog_Model_ProductPrice->saveVariantPriceModel($price, $this->id(), $this->get('product_id'));
        }
        return parent::onAfterSave();
    }

}
