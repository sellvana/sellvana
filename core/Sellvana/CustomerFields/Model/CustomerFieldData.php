<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomerFields_Model_CustomerFieldData
 *
 * @property int                                 $id
 * @property int                                 $product_id
 * @property string                              $_fieldset_ids
 * @property string                              $_add_field_ids
 * @property string                              $_hide_field_ids
 * @property string                              $_data_serialized
 * @property string                              $Color
 * @property string                              $size
 * @property string                              $ColorABC
 * @property string                              $storage
 * @property string                              $test
 * @property string                              $test1
 * @property string                              $test2
 *
 * DI
 * @property Sellvana_CustomerFields_Model_Field $Sellvana_CustomerFields_Model_Field
 * @property Sellvana_CustomerFields_Model_FieldOption $Sellvana_CustomerFields_Model_FieldOption
 */
class Sellvana_CustomerFields_Model_CustomerFieldData extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_customer_field_data';
    protected static $_importExportProfile = [
        'skip' => [],
        'related' => [
            'customer_id' => 'Sellvana_Customer_Model_Customer.id',
        ],
        'unique_id' => ['customer_id'],
    ];
    protected static $_fieldTypeColumns = [
        'options'    => 'value_id',
        'varchar'    => 'value_var',
        'text'       => 'value_text',
        'int'        => 'value_int',
        'tinyint'    => 'value_int',
        'decimal'    => 'value_dec',
        'date'       => 'value_date',
        'datetime'   => 'value_date',
        'serialized' => 'data_serialized',
    ];

    protected static $_autoCreateOptions = false;

    /**
     * @param boolean $flag
     * @return $this
     */
    public function setAutoCreateOptions($flag)
    {
        static::$_autoCreateOptions = $flag;

        return $this;
    }

    /**
     * @param Sellvana_Customer_Model_Customer[] $customers
     * @return $this
     */
    public function saveCustomersFieldData($customers)
    {
        $fields = $this->Sellvana_CustomerFields_Model_Field->getAllFields();
        //$this->Sellvana_CatalogFields_Model_FieldOption->preloadAllFieldsOptions();

        $cIds = $this->BUtil->arrayToOptions($customers, '.id');
        if (!$cIds) {
            return $this;
        }

        /** @var Sellvana_CustomerFields_Model_CustomerFieldData[][][] $fieldsData */
        $rawFieldsData = $this->orm('cfd')->where_in('customer_id', $cIds)->find_many();
        $fieldsData    = [];
        foreach ($rawFieldsData as $rawData) {
            $cId = $rawData->get('customer_id');
            if (empty($fieldsData[$cRawId])) {
                $fieldsData[$cRawId] = [];
            }

            $rawFieldId = $rawData->get('field_id');
            if (empty($fieldsData[$cRawId][$rawFieldId])) {
                $fieldsData[$cRawId][$rawFieldId] = [];
            }

            array_push($fieldsData[$cRawId][$rawFieldId], $rawData);
        }

        $options        = $this->Sellvana_CustomerFields_Model_FieldOption->preloadAllFieldsOptions()
                                                                         ->getAllFieldsOptions();
        $optionsByLabel = [];
        foreach ($options as $fieldId => $fieldOptions) {
            /** @var  $option Sellvana_CustomerFields_Model_FieldOption*/
            foreach ($fieldOptions as $optionId => $option) {
                $optionsByLabel[$fieldId][strtolower($option->get('label'))] = $option->id();
            }
        }
        foreach ($customers as $customer) { // go over customers
            $cId          = $customer->id();
            $cData        = $customer->as_array();
            $saveCustomer = false;
            foreach ($cData as $fieldCode => $value) { // go over all customer fields data
                if (empty($fields[$fieldCode])) {
                    continue;
                }

                $field       = $fields[$fieldCode];
                $fId         = $field->id();
                $fieldType   = $field->get('table_field_type');
                $tableColumn = static::$_fieldTypeColumns[$fieldType];

                if ($fieldType === 'options') {
                    $value = explode(',', $value);
                } elseif (!is_array($value)) {
                    $value = [$value];
                }

                foreach ($value as $singleValue) {
                    if (null !== $customer->get($fieldCode)) { // if this customer has this field data
                        if (!empty($fieldsData[$cId][$fId])) { // if this field data record already exists
                            $fData = array_shift($fieldsData[$cId][$fId]);
                            if (!empty($cData['_custom_fields_remove'])
                                    && in_array($fId, $cData['_custom_fields_remove'])) {
                                $fData->delete();
                                $customer->set($fieldCode, null);
                                continue;
                            }
                        } else { // if this is a new entry
                            $fData = $this->create([
                                'customer_id' => $cId,
                                'field_id'   => $fId,
                            ]);
                        }
                        if ($fieldType === 'options') {
                            $valueLower = strtolower($singleValue);
                            if (!empty($optionsByLabel[$fId][$valueLower])) { // option exists?
                                $singleValue = $optionsByLabel[$fId][$valueLower];
                            } else {                                   // option doesn't exist
                                if (static::$_autoCreateOptions) { // allow option auto-creation?
                                    $optionId                          = $this->Sellvana_CustomerFields_Model_FieldOption->create([
                                        'field_id' => $fId,
                                        'label'    => $singleValue,
                                    ])->save()->id();
                                    $singleValue                       = $optionId;
                                    $optionsByLabel[$fId][$valueLower] = $optionId;
                                } else { // don't auto-create
                                    $singleValue = null;
                                }
                            }
                        }
                        if ($fieldType === 'serialized') {
                            $customer->setData("field_data/{$fieldCode}", $singleValue);
                            $saveCustomer = true;
                        } else {
                            $fData->set($tableColumn, $singleValue);
                            $fData->save();
                        }
                    } else { // this customer doesn't have data for this field
                        if (!empty($fieldsData[$cId][$fId])) { // there's old data
                            foreach ($fieldsData[$cId][$fId] as $wrongData) {
                                $wrongData->set($tableColumn, null); // delete old data record for this customer/field
                            }
                        }
                    }
                }
            }

            // cleaning up deleted values
            foreach ($fieldsData as $custData) {
                foreach ($custData as $fieldData) {
                    foreach ($fieldData as $valueData) {
                        $valueData->delete();
                    }
                }
            }

            if ($saveCustomer) {
                $customer->save();
            }
        }

        return $this;
    }

    /**
     * @param array $customerIds
     * @return Sellvana_CustomerFields_Model_CustomerFieldData[][]
     */
    public function fetchCustomersFieldData($customerIds)
    {
        return $this->orm('cf')
                    ->join('Sellvana_CustomerFields_Model_Field', ['f.id', '=', 'cf.field_id'], 'f')
                    ->left_outer_join('Sellvana_CustomerFields_Model_FieldOption', ['fo.id', '=', 'cf.value_id'], 'fo')
                    ->select([
                        'cf.*',
                        'f.field_code',
                        'f.field_name',
                        'f.required',
                        'f.admin_input_type',
                        'f.table_field_type'
                    ])
                    ->where_in('cf.customer_id', $customerIds)
                    ->find_many_assoc(['customer_id', 'id']);
    }

    /**
     * @param Sellvana_Customer_Model_Customer[] $customers
     *
     * @return $this
     */
    public function collectProductsFieldData($customers)
    {
        $sutomerIds = $this->BUtil->arrayToOptions($customers, '.id');
        if (!$sutomerIds) {
            return $this;
        }

        $fieldsData = $this->fetchCustomersFieldData($sutomerIds);
        foreach ($customers as $customer) {
            if (empty($fieldsData[$customer->id()])) {
                continue;
            }
            foreach ($fieldsData[$customer->id()] as $row) {
                $column = static::$_fieldTypeColumns[$row->get('table_field_type')];
                $value  = $row->get($column);
                if ($row->get('table_field_type') === 'options') {
                    $options = $this->Sellvana_CustomerFields_Model_FieldOption->getFieldOptions($row->get('field_id'));
                    if (!empty($options[$value])) {
                        $value = $options[$value];
                    }
                    if ($oldValue = $customer->get($row->get('field_code'))) {
                        $value = $oldValue . ',' . $value;
                    }
                }
                $customer->set($row->get('field_code'), $value);
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

    /**
     * @param BORM $orm
     * @param      $fieldCode
     * @param      $value
     * @return $this
     */
    public function addOrmFilter(BORM $orm, $fieldCode, $value)
    {
        $field     = $this->Sellvana_CustomerFields_Model_Field->getField($fieldCode);
        $pAlias    = $orm->table_alias();
        $fAlias    = "f_{$fieldCode}";
        $pfdAlias  = "pfd_{$fieldCode}";
        $pfdColumn = static::$_fieldTypeColumns[$field->get('table_field_type')];

        $orm->join('Sellvana_CustomerFields_Model_CustomerFieldData', ["{$pfdAlias}.customer_id", '=', "{$pAlias}.id"], $pfdAlias)
            ->join('Sellvana_CustomerFields_Model_Field', ["{$fAlias}.id", '=', "{$pfdAlias}.field_id"], $fAlias)
            ->where("{$fAlias}.field_code", $fieldCode)
            ->where("{$pfdAlias}.{$pfdColumn}", $value);

        return $this;
    }
}
