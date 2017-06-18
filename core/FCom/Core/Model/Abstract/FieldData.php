<?php

/**
 * Class FCom_Core_Model_Abstract_FieldData
 *
 * @property int $id
 * @property int $product_id
 * @property int $set_id
 * @property int $field_id
 * @property int $position
 * @property int $site_id
 * @property string $locale
 * @property int $value_int
 * @property float $value_dec
 * @property string $value_var
 * @property string $value_text
 * @property string $value_date
 *
 * @property FCom_Core_Model_Field $FCom_Core_Model_Field
 * @property FCom_Core_Model_FieldOption $FCom_Core_Model_FieldOption
 * @property FCom_Core_Model_Fieldset $FCom_Core_Model_Fieldset
 * @property Sellvana_CatalogFields_Main $Sellvana_CatalogFields_Main
 *
 * @property Sellvana_MultiSite_Main $Sellvana_MultiSite_Main
 */
abstract class FCom_Core_Model_Abstract_FieldData extends FCom_Core_Model_Abstract
{
    /* Properties to be declared in implemented class */
    protected static $_origClass;# = __CLASS__;
    protected static $_table;# = 'fcom_product_field_data';
    protected static $_fieldType;# = 'product';
    protected static $_mainModel;# = 'Sellvana_Catalog_Model_Product';
    protected static $_mainModelKeyField;# = 'product_id';
    protected static $_useMultisite = false;
    /* END */

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
                static::$_mainModelKeyField => static::$_mainModel . '.id',
                'set_id' => 'FCom_Core_Model_Fieldset.id',
                'field_id' => 'FCom_Core_Model_Field.id',
                'value_id' => 'FCom_Core_Model_FieldOption.id',
            ],
            'unique_key' => [static::$_mainModelKeyField, 'field_id'],
        ];
        if ($this->_useMultisite()) {
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
     * @param FCom_Core_Model_Abstract[] $models
     *
     * @return $this
     */
    public function saveModelsFieldData($models)
    {
        if ($this->Sellvana_CatalogFields_Main->isDisabled()) {
            return $this;
        }

        $defaultSet = $this->FCom_Core_Model_Fieldset->loadWhere([
            'set_code' => 'default',
            'set_type' => static::$_fieldType,
        ]);

        $fields = $this->FCom_Core_Model_Field->getAllFields(static::$_fieldType);
        //$this->FCom_Core_Model_FieldOption->preloadAllFieldsOptions();

        $pIds = $this->BUtil->arrayToOptions($models, '.id');
        if (!$pIds) {
            return $this;
        }
        /** @var Sellvana_CatalogFields_Model_ProductFieldData[][][] $fieldsData */
        $rawFieldsData = $this->orm('pf')->where_in(static::$_mainModelKeyField, $pIds)->find_many();
        $fieldsData = [];
        foreach ($rawFieldsData as $rawData) {
            $rawDataId = $rawData->get(static::$_mainModelKeyField);
            $rawFieldId = $rawData->get('field_id');
            if (empty($fieldsData[$rawDataId])) {
                $fieldsData[$rawDataId] = [];
            }

            if (empty($fieldsData[$rawDataId][$rawFieldId])) {
                $fieldsData[$rawDataId][$rawFieldId] = [];
            }

            array_push($fieldsData[$rawDataId][$rawFieldId], $rawData);
        }

        $options = $this->FCom_Core_Model_FieldOption->preloadAllFieldsOptions()->getAllFieldsOptions();
        $optionsByLabel = [];
        foreach ($options as $fieldId => $fieldOptions) {
            foreach ($fieldOptions as $optionId => $option) {
                $optionsByLabel[$fieldId][strtolower($option->get('label'))] = $option->id();
            }
        }
        foreach ($models as $model) { // go over products
#echo "<pre>"; debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS); echo "</pre>"; var_dump($model->as_array());
            $pId = $model->id();
            $pData = $model->as_array();
            $saveModel = false;
            foreach ($pData as $fieldCode => $value) { // go over all model fields data
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
                    if (null !== $model->get($fieldCode)) { // if this model has this field data
                        if (!empty($fieldsData[$pId][$fId])) { // if this field data record already exists
                            $fData = array_shift($fieldsData[$pId][$fId]);
                            if (!empty($pData['_custom_fields_remove']) && in_array($fId, $pData['_custom_fields_remove'])) {
                                $fData->delete();
                                $model->set($fieldCode, null);
                                continue;
                            }
                        } else { // if this is a new entry
                            $fData = $this->create([
                                static::$_mainModelKeyField => $pId,
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
                                    $optionId = $this->FCom_Core_Model_FieldOption->create([
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
                            $model->setData("field_data/{$fieldCode}", $singleValue);
                            $saveModel = true;
                        } else {
                            $fData->set($tableColumn, $singleValue);
                            $fData->save();
                        }
                    } else { // this model doesn't have data for this field
                        if (!empty($fieldsData[$pId][$fId])) { // there's old data
                            foreach ($fieldsData[$pId][$fId] as $wrongData) {
                                $wrongData->set($tableColumn, null); // delete old data record for this model/field
                            }
                        }
                    }
                }
            }

            // cleaning up deleted values
            foreach ($fieldsData as $modelData) {
                foreach ($modelData as $fieldData) {
                    foreach ($fieldData as $valueData) {
                        if ($this->_useMultisite() && $valueData->get('site_id')) {
                            continue;
                        }
                        $valueData->delete();
                    }
                }
            }

            if ($saveModel) {
                $model->save();
            }
        }
        return $this;
    }

    /**
     * @param array $modelIds
     * @return Sellvana_CatalogFields_Model_ProductFieldData[][]
     */
    public function fetchModelsFieldData($modelIds)
    {
        $orm = $this->orm('pf')
            ->join('FCom_Core_Model_Field', ['f.id', '=', 'pf.field_id'], 'f')
            ->left_outer_join('FCom_Core_Model_FieldOption', ['fo.id', '=', 'pf.value_id'], 'fo')
            ->left_outer_join('FCom_Core_Model_Fieldset', ['fs.id', '=', 'pf.set_id'], 'fs')
            ->select(['pf.*', 'f.field_code', 'f.field_name', 'f.admin_input_type', 'f.frontend_show', 'f.multilanguage', 'f.data_serialized', 'f.table_field_type', 'fs.set_name'])
            ->where_in('pf.' . static::$_mainModelKeyField, $modelIds);

        $this->BEvents->fire($this->_origClass() . '::' . __FUNCTION__, ['orm' => $orm]);
        /** @var BORM $orm */
        return $orm->find_many_assoc([static::$_mainModelKeyField, 'id']);
    }

    public function getModelsFieldSetData($modelIds)
    {
        $data = $this->fetchModelsFieldData($modelIds);
        $this->BEvents->fire($this->_origClass() . '::' . __FUNCTION__ . ':afterFetchModelsFieldData', ['data' => &$data]);

        $fieldsData = [];
        foreach ($data as $modelId => $pData) {
            /**
             * @var string $fieldCode
             * @var Sellvana_CatalogFields_Model_ProductFieldData $row
             */
            foreach ($pData as $row) {
                $setId = $row->get('set_id') ?: '';

                if (empty($fieldsData[$modelId][$setId])) {
                    $fieldsData[$modelId][$setId] = [
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
                    $options = $this->FCom_Core_Model_FieldOption->getFieldOptions($fieldId);
                    if (!empty($options[$value])) {
                        $value = $options[$value];
                    }
                }

                $field = [
                    'id' => $fieldId,
                    'site_id' => $row->get('site_id') ?: '',
                    'field_code' => $row->get('field_code'),
                    'field_name' => $row->get('field_name'),
                    'admin_input_type' => $row->get('admin_input_type'),
                    'frontend_show' => $row->get('frontend_show'),
                    'multilanguage' => $row->get('multilanguage'),
                    'value' => $value,
                    'position' => $row->get('position'),
                    'required' => $row->get('required'),
                    'serialized' => json_encode($row->as_array())
                ];

                if ($row->get('table_field_type') === 'options') {
                    $field['options'] = $this->FCom_Core_Model_FieldOption->getFieldOptions($fieldId, false, 'label');
                }

                $found = false;
                if ($field['admin_input_type'] == 'multiselect') {
                    foreach ($fieldsData[$modelId][$setId]['fields'] as &$oldField) {
                        if ($this->shouldCombineValues($oldField, $field)) {
                            $oldField['value'] .= ',' . $field['value'];
                            $found = true;
                            break;
                        }
                    }
                }

                if (!$found) {
                    $fieldsData[$modelId][$setId]['fields'][] = $field;
                }
            }
        }

        $this->BEvents->fire($this->_origClass() . '::' . __FUNCTION__, ['data' => &$fieldsData]);

        return $fieldsData;
    }

    /**
     * @param Sellvana_Catalog_Model_Product[] $models
     * @param array $fieldCodes
     *
     * @return $this
     */
    public function collectModelsFieldData($models, $fieldCodes = [])
    {
        //$this->FCom_Core_Model_FieldOption->preloadAllFieldsOptions();
        $modelIds = $this->BUtil->arrayToOptions($models, '.id');
        if (!$modelIds) {
            return $this;
        }

        $fieldsData = $this->fetchModelsFieldData($modelIds);
        foreach ($models as $model) {
            if (empty($fieldsData[$model->id()])) {
                continue;
            }
            foreach ($fieldsData[$model->id()] as $row) {
                if ($this->_useMultisite() && !$this->Sellvana_MultiSite_Main->isFieldDataBelongsToThisSite($row)) {
                    continue;
                }
                $column = static::$_fieldTypeColumns[$row->get('table_field_type')];
                $value  = $row->get($column);
                //if ($row->get('admin_input_type') === 'multiselect') {
                if ($row->get('table_field_type') === 'options') {
                    $options = $this->FCom_Core_Model_FieldOption->getFieldOptions($row->get('field_id'));
                    if (!empty($options[$value])) {
                        $value = $options[$value];
                    }
                    if ($oldValue = $model->get($row->get('field_code'))) {
                        $oldValues = explode(',', $oldValue);
                        if (!in_array($value, $oldValues)) {
                            $value = $oldValue . ',' . $value;
                        }
                    }
                }
                $model->set($row->get('field_code'), $value);
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
        $field = $this->FCom_Core_Model_Field->getField($fieldCode);
        $pAlias = $orm->table_alias();
        $fAlias = "f_{$fieldCode}";
        $pfdAlias = "pfd_{$fieldCode}";
        $pfdColumn = static::$_fieldTypeColumns[$field->get('table_field_type')];
        $dataModel = $this->_origClass();

        $orm->join($dataModel, [$pfdAlias . '.' . static::$_mainModelKeyField, '=', "{$pAlias}.id"], $pfdAlias)
            ->join('FCom_Core_Model_Field', ["{$fAlias}.id", '=', "{$pfdAlias}.field_id"], $fAlias)
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
        if ($this->_useMultisite()) {
            return $this->Sellvana_MultiSite_Main->shouldCombineFieldDataValues($oldField, $field);
        }

        return $oldField['field_code'] == $field['field_code'];
    }

    protected function _useMultisite()
    {
        if (!static::$_useMultisite) {
            return false;
        }

        static $multiSiteLoaded;
        if (null === $multiSiteLoaded) {
            $multiSiteLoaded = $this->BModuleRegistry->isLoaded('Sellvana_MultiSite');
        }
        if (!$multiSiteLoaded) {
            return false;
        }

        return true;
    }

}
