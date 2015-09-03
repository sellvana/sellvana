<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogFields_Model_ProductFieldData
 *
 * @property int $id
 * @property int $product_id
 * @property int $set_id
 * @property int $value_id
 * @property int $value_int
 * @property float $value_dec
 * @property string $value_var
 * @property string $value_text
 * @property string $value_date
 *
 * @property Sellvana_CatalogFields_Model_Field $Sellvana_CatalogFields_Model_Field
 * @property Sellvana_CatalogFields_Model_FieldOption $Sellvana_CatalogFields_Model_FieldOption
 */
class Sellvana_CatalogFields_Model_ProductFieldData extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product_field_data';

    protected static $_fieldTypeColumns = [
        'options'       => 'value_id',
        'varchar'       => 'value_var',
        'text'          => 'value_text',
        'int'           => 'value_int',
        'decimal'       => 'value_dec',
        'date'          => 'value_date',
        'datetime'      => 'value_date',
        'serialized'    => 'data_serialized',
    ];

    protected static $_autoCreateOptions = false;

    public function setAutoCreateOptions($flag)
    {
        static::$_autoCreateOptions = $flag;
        return $this;
    }

    /**
     * @param Sellvana_Catalog_Model_Product[] $products
     *
     * @return $this
     */
    public function saveProductsFieldData($products)
    {
        $fields = $this->Sellvana_CatalogFields_Model_Field->getAllFields();
        //$this->Sellvana_CatalogFields_Model_FieldOption->preloadAllFieldsOptions();

        $pIds = $this->BUtil->arrayToOptions($products, '.id');
        if (!$pIds) {
            return $this;
        }
        /** @var Sellvana_CatalogFields_Model_ProductFieldData[][] $fieldsData */
        $fieldsData = $this->orm('pfd')->where_in('product_id', $pIds)
            ->find_many_assoc(['product_id', 'field_id']);

        $options = $this->Sellvana_CatalogFields_Model_FieldOption->preloadAllFieldsOptions()->getAllFieldsOptions();
        $optionsByLabel = [];
        foreach ($options as $fieldId => $fieldOptions) {
            foreach ($fieldOptions as $optionId => $option) {
                $optionsByLabel[$fieldId][strtolower($option->get('label'))] = $option->id();
            }
        }
        foreach ($products as $product) { // go over products
#echo "<pre>"; debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS); echo "</pre>"; var_dump($product->as_array());
            $pId = $product->id();
            $pData = $product->as_array();
            $saveProduct = false;
            foreach ($pData as $fieldCode => $value) { // go over all product fields data
                if (empty($fields[$fieldCode])) {
                    continue;
                }
                $field = $fields[$fieldCode];
                $fId = $field->id();
                $fieldType = $field->get('table_field_type');
                if (null !== $product->get($fieldCode)) { // if this product has this field data
                    if (!empty($fieldsData[$pId][$fId])) { // if this field data record already exists
                        $fData = $fieldsData[$pId][$fId];
                    } else { // if this is a new entry
                        $fData = $this->create([
                            'product_id' => $pId,
                            'field_id' => $fId,
                        ]);
                    }
                    if ($fieldType === 'options') {
                        $valueLower = strtolower($value);
                        if (!empty($optionsByLabel[$fId][$valueLower])) { // option exists?
                            $value = $optionsByLabel[$fId][$valueLower];
                        } else {                                   // option doesn't exist
                            if (static::$_autoCreateOptions) { // allow option auto-creation?
                                $optionId = $this->Sellvana_CatalogFields_Model_FieldOption->create([
                                    'field_id' => $fId,
                                    'label' => $value,
                                ])->save()->id();
                                $value = $optionId;
                                $optionsByLabel[$fId][$valueLower] = $optionId;
                            } else { // don't auto-create
                                $value = null;
                            }
                        }
                    }
                    if ($fieldType === 'serialized') {
                        $product->setData("field_data/{$fieldCode}", $value);
                        $saveProduct = true;
                    } else {
                        $tableColumn = static::$_fieldTypeColumns[$fieldType];
                        $fData->set($tableColumn, $value);
                        $fData->save();
                    }
                } else { // this product doesn't have data for this field
                    if (!empty($fieldsData[$pId][$fId])) { // there's old data
                        $tableColumn = static::$_fieldTypeColumns[$fieldType];
                        $fieldsData[$pId][$fId]->set($tableColumn, null); // delete old data record for this product/field
                    }
                }
            }
            if ($saveProduct) {
                $product->save();
            }
        }
        return $this;
    }

    /**
     * Delete product field data
     * @param  Sellvana_Catalog_Model_Product $p 
     * @param  Array $pfdIds 
     * @return mixed
     */
    public function deleteProductsFieldData($p, $pfdIds) {
        foreach ($pfdIds as $setId => $pfdIds) {
            foreach ($pfdIds as $pfdId) {
                $pfd = $this->orm('pf')->where([
                    'pfd.product_id' => $p->id(),
                    'pfd.set_id' => $setId,
                    'pfd.field_id' => $pfdId
                ])->find_one();

                if ($pfd) {
                    $pfd->set('set_id', null)->save();
                }
            }
        }
    }

    /**
     * @param array $productIds
     * @return Sellvana_CatalogFields_Model_ProductFieldData[][]
     */
    public function fetchProductsFieldData($productIds)
    {
        return $this->orm('pf')
            ->join('Sellvana_CatalogFields_Model_Field', ['f.id', '=', 'pf.field_id'], 'f')
            ->left_outer_join('Sellvana_CatalogFields_Model_FieldOption', ['fo.id', '=', 'pf.value_id'], 'fo')
            ->left_outer_join('Sellvana_CatalogFields_Model_Set', ['fs.id', '=', 'pf.set_id'], 'fs')
            ->select(['pf.*', 'f.field_code', 'f.field_name', 'f.required', 'f.admin_input_type', 'f.table_field_type', 'fs.set_name'])
            ->where_in('pf.product_id', $productIds)
            ->find_many_assoc(['product_id', 'id']);
    }

    public function getProductFieldSetData($productIds)
    {
        $data = $this->fetchProductsFieldData($productIds);

        $fieldsData = [];
        foreach ($data as $productId => $pData) {
            /**
             * @var string $fieldCode
             * @var Sellvana_CatalogFields_Model_ProductFieldData $row
             */
            foreach ($pData as $row) {
                $setId = $row->get('set_id') ?: '';

                if (empty($fieldsData[$productId][$setId])) {
                    $fieldsData[$productId][$setId] = [
                        'collapsed' => 'false',
                        'id' => $setId,
                        'set_name' => ($row->get('set_name')) ?: '',
                        'fields' => [],
                    ];
                }

                $column = static::$_fieldTypeColumns[$row->get('table_field_type')];
                $value = $row->get($column);
                $fieldId = $row->get('field_id');

                if ($row->get('table_field_type') === 'options') {
                    $options = $this->Sellvana_CatalogFields_Model_FieldOption->getFieldOptions($fieldId);
                    if (!empty($options[$value])) {
                        $value = $options[$value];
                    }
                }

                $field = [
                    'id' => $fieldId,
                    'field_code' => $row->get('field_code'),
                    'field_name' => $row->get('field_name'),
                    'admin_input_type' => $row->get('admin_input_type'),
                    'value' => $value,
                    'position' => $row->get('position'),
                    'required' => $row->get('required')
                ];

                if ($row->get('table_field_type') === 'options') {
                    $field['options'] = $this->Sellvana_CatalogFields_Model_FieldOption->getFieldOptions($fieldId, false, 'label');
                }

                $fieldsData[$productId][$setId]['fields'][] = $field;
            }
        }

        return $fieldsData;
    }

    /**
     * @param Sellvana_Catalog_Model_Product[] $products
     * @param array $fieldCodes
     *
     * @return $this
     */
    public function collectProductsFieldData($products, $fieldCodes = [])
    {
        //$this->Sellvana_CatalogFields_Model_FieldOption->preloadAllFieldsOptions();
        $productIds = $this->BUtil->arrayToOptions($products, '.id');
        if (!$productIds) {
            return $this;
        }

        $fieldsData = $this->fetchProductsFieldData($productIds);
        foreach ($products as $product) {
            if (empty($fieldsData[$product->id()])) {
                continue;
            }
            foreach ($fieldsData[$product->id()] as $row) {
                $column = static::$_fieldTypeColumns[$row->get('table_field_type')];
                $value = $row->get($column);
                if ($row->get('table_field_type') === 'options') {
                    $options = $this->Sellvana_CatalogFields_Model_FieldOption->getFieldOptions($row->get('field_id'));
                    if (!empty($options[$value])) {
                        $value = $options[$value];
                    }
                }
                $product->set($row->get('field_code'), $value);
            }
        }
        return $this;
    }

    /**
     * @param string $type
     * @return bool|string
     */
    public function getTableColumn($type)
    {
        if (!empty(static::$_fieldTypeColumns[$type])) {
            return static::$_fieldTypeColumns[$type];
        } else {
            return false;
        }
    }
}
