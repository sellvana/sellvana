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
 * @property Sellvana_CatalogFields_Model_Set $Sellvana_CatalogFields_Model_Set
 *
 * @property Sellvana_MultiSite_Main $Sellvana_MultiSite_Main
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
        'tinyint'       => 'value_int',
        'decimal'       => 'value_dec',
        'date'          => 'value_date',
        'datetime'      => 'value_date',
        'serialized'    => 'data_serialized',
    ];

    protected static $_importExportProfile = 'THIS.importExportProfile';

    protected static $_autoCreateOptions = false;

    public function importExportProfile()
    {
        $profile = [
            'related' => [
                'product_id' => 'Sellvana_Catalog_Model_Product.id',
                'set_id' => 'Sellvana_CatalogFields_Model_Set.id',
                'field_id' => 'Sellvana_CatalogFields_Model_Field.id',
                'value_id' => 'Sellvana_CatalogFields_Model_FieldOption.id',
            ],
            'unique_key' => ['product_id', 'field_id'],
        ];
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite')) {
            $profile['related']['site_id'] = 'Sellvana_MultiSite_Model_Site.id';
            $profile['unique_key'][] = 'site_id';
        }

        return $profile;
    }

    public function setAutoCreateOptions($flag)
    {
        static::$_autoCreateOptions = $flag;
        return $this;
    }

    public function getAutoCreateOptions()
    {
        return static::$_autoCreateOptions;
    }

    /**
     * @param Sellvana_Catalog_Model_Product[] $products
     *
     * @return $this
     */
    public function saveProductsFieldData($products)
    {
        $defaultSet = $this->Sellvana_CatalogFields_Model_Set->loadWhere([
            'set_code' => 'default',
            'set_type' => 'product',
        ]);

        $fields = $this->Sellvana_CatalogFields_Model_Field->getAllFields();
        //$this->Sellvana_CatalogFields_Model_FieldOption->preloadAllFieldsOptions();

        $pIds = $this->BUtil->arrayToOptions($products, '.id');
        if (!$pIds) {
            return $this;
        }
        /** @var Sellvana_CatalogFields_Model_ProductFieldData[][][] $fieldsData */
        $rawFieldsData = $this->orm('pf')->where_in('product_id', $pIds)->find_many();
        $fieldsData = [];
        foreach ($rawFieldsData as $rawData) {
            if (empty($fieldsData[$rawData->get('product_id')])) {
                $fieldsData[$rawData->get('product_id')] = [];
            }

            if (empty($fieldsData[$rawData->get('product_id')][$rawData->get('field_id')])) {
                $fieldsData[$rawData->get('product_id')][$rawData->get('field_id')] = [];
            }

            array_push($fieldsData[$rawData->get('product_id')][$rawData->get('field_id')], $rawData);
        }

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
                $tableColumn = static::$_fieldTypeColumns[$fieldType];

                if ($fieldType === 'options') {
                    $value = explode(',', $value);
                }
                elseif (!is_array($value)) {
                    $value = [$value];
                }

                foreach ($value as $singleValue) {
                    if (null !== $product->get($fieldCode)) { // if this product has this field data
                        if (!empty($fieldsData[$pId][$fId])) { // if this field data record already exists
                            $fData = array_shift($fieldsData[$pId][$fId]);
                            if (!empty($pData['_custom_fields_remove']) && in_array($fId, $pData['_custom_fields_remove'])) {
                                $fData->delete();
                                $product->set($fieldCode, null);
                                continue;
                            }
                        } else { // if this is a new entry
                            $fData = $this->create([
                                'product_id' => $pId,
                                'field_id' => $fId,
                                'set_id' => $defaultSet ? $defaultSet->id() : null,
                            ]);
                        }
                        if ($fieldType === 'options') {
                            $valueLower = strtolower($singleValue);
                            if (!empty($optionsByLabel[$fId][$valueLower])) { // option exists?
                                $singleValue = $optionsByLabel[$fId][$valueLower];
                            } else {                                   // option doesn't exist
                                if (static::$_autoCreateOptions) { // allow option auto-creation?
                                    $optionId = $this->Sellvana_CatalogFields_Model_FieldOption->create([
                                        'field_id' => $fId,
                                        'label' => $singleValue,
                                    ])->save()->id();
                                    $singleValue = $optionId;
                                    $optionsByLabel[$fId][$valueLower] = $optionId;
                                } else { // don't auto-create
                                    $singleValue = null;
                                }
                            }
                        }
                        if ($fieldType === 'serialized') {
                            $product->setData("field_data/{$fieldCode}", $singleValue);
                            $saveProduct = true;
                        } else {
                            $fData->set($tableColumn, $singleValue);
                            $fData->save();
                        }
                    } else { // this product doesn't have data for this field
                        if (!empty($fieldsData[$pId][$fId])) { // there's old data
                            foreach ($fieldsData[$pId][$fId] as $wrongData) {
                                $wrongData->set($tableColumn, null); // delete old data record for this product/field
                            }
                        }
                    }
                }
            }

            // cleaning up deleted values
            foreach ($fieldsData as $prodData) {
                foreach ($prodData as $fieldData) {
                    foreach ($fieldData as $valueData) {
                        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite') && $valueData->get('site_id')) {
                            continue;
                        }
                        $valueData->delete();
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
     * @param array $productIds
     * @return Sellvana_CatalogFields_Model_ProductFieldData[][]
     */
    public function fetchProductsFieldData($productIds)
    {
        $orm = $this->orm('pf')
            ->join('Sellvana_CatalogFields_Model_Field', ['f.id', '=', 'pf.field_id'], 'f')
            ->left_outer_join('Sellvana_CatalogFields_Model_FieldOption', ['fo.id', '=', 'pf.value_id'], 'fo')
            ->left_outer_join('Sellvana_CatalogFields_Model_Set', ['fs.id', '=', 'pf.set_id'], 'fs')
            ->select(['pf.*', 'f.field_code', 'f.field_name', 'f.admin_input_type', 'f.table_field_type', 'fs.set_name'])
            ->where_in('pf.product_id', $productIds);

        $this->BEvents->fire(__METHOD__, ['orm' => $orm]);

        return $orm->find_many_assoc(['product_id', 'id']);
    }

    public function getProductFieldSetData($productIds)
    {
        $data = $this->fetchProductsFieldData($productIds);
        $this->BEvents->fire(__METHOD__ . ':afterFetchProductsFieldData', ['data' => &$data]);

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
                    'required' => $row->get('required'),
                    'serialized' => json_encode($row->as_array())
                ];

                if ($row->get('table_field_type') === 'options') {
                    $field['options'] = $this->Sellvana_CatalogFields_Model_FieldOption->getFieldOptions($fieldId, false, 'label');
                }

                $found = false;
                if ($field['admin_input_type'] == 'multiselect') {
                    foreach ($fieldsData[$productId][$setId]['fields'] as &$oldField) {
                        if ($this->shouldCombineValues($oldField, $field)) {
                            $oldField['value'] .= ',' . $field['value'];
                            $found = true;
                            break;
                        }
                    }
                }

                if (!$found) {
                    $fieldsData[$productId][$setId]['fields'][] = $field;
                }
            }
        }

        $this->BEvents->fire(__METHOD__, ['data' => &$fieldsData]);

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
                if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite')
                    && $this->Sellvana_MultiSite_Main->isFieldDataBelongsToThisSite($row)) {
                    continue;
                }
                $column = static::$_fieldTypeColumns[$row->get('table_field_type')];
                $value = $row->get($column);
                if ($row->get('table_field_type') === 'options') {
                    $options = $this->Sellvana_CatalogFields_Model_FieldOption->getFieldOptions($row->get('field_id'));
                    if (!empty($options[$value])) {
                        $value = $options[$value];
                    }
                    if ($oldValue = $product->get($row->get('field_code'))) {
                        $value = $oldValue . ',' . $value;
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

    public function addOrmFilter(BORM $orm, $fieldCode, $value)
    {
        $field = $this->Sellvana_CatalogFields_Model_Field->getField($fieldCode);
        $pAlias = $orm->table_alias();
        $fAlias = "f_{$fieldCode}";
        $pfdAlias = "pfd_{$fieldCode}";
        $pfdColumn = static::$_fieldTypeColumns[$field->get('table_field_type')];

        $orm->join('Sellvana_CatalogFields_Model_ProductFieldData', ["{$pfdAlias}.product_id", '=', "{$pAlias}.id"], $pfdAlias)
            ->join('Sellvana_CatalogFields_Model_Field', ["{$fAlias}.id", '=', "{$pfdAlias}.field_id"], $fAlias)
            ->where("{$fAlias}.field_code", $fieldCode)
            ->where("{$pfdAlias}.{$pfdColumn}", $value);

        return $this;
    }

    /**
     * @param $oldField
     * @param $field
     * @return bool
     */
    protected function shouldCombineValues($oldField, $field)
    {
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite')) {
            return $this->Sellvana_MultiSite_Main->shouldCombineFieldDataValues($oldField, $field);
        }

        return $oldField['field_code'] == $field['field_code'];
    }
}
