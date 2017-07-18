<?php

/**
 * Class FCom_Core_Model_Field
 *
 * @property int $id
 * @property string $field_type (product|will be add more value)
 * @property string $field_code
 * @property string $field_name
 * @property string $table_field_type
 * @property string $admin_input_type
 * @property string $frontend_label
 * @property int $frontend_show
 * @property string $config_json
 * @property int $sort_order
 * @property string $facet_select (No|Exclusive|Inclusive)
 * @property int $system
 * @property int $multilanguage
 * @property string $validation
 * @property int $required
 *
 * DI
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property FCom_Core_Model_FieldOption $FCom_Core_Model_FieldOption
 */
class FCom_Core_Model_Field extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_field';

    protected static $_fieldOptions = [
        'field_type'      => [
        ],
        'table_field_type' => [
            'varchar'      => (('Short Text')),
            'text'         => (('Long Text')),
            'options'      => (('Options')),
            'int'          => (('Integer')),
            'tinyint'      => (('Tiny Integer')),
            'decimal'      => (('Decimal')),
            'date'         => (('Date')),
            'datetime'     => (('Date/Time')),
            'serialized'   => (('Serialized')),
        ],
        'admin_input_type' => [
            'text'         => (('Text Line')),
            'textarea'     => (('Text Area')),
            'select'       => (('Drop down')),
            'multiselect'  => (('Multiple Select')),
            'boolean'      => (('Yes/No')),
            'wysiwyg'      => (('WYSIWYG editor'))
        ],
        'frontend_show'    => [
            '1'            => (('Yes')),
            '0'            => (('No'))
        ],
        'swatch_type'      => [
            'N' => (('None')),
            'C' => (('Color')),
            'I' => (('Image')),
        ],
        'account_edit' => [
            '1' => (('Yes')),
            '0' => (('No'))
        ],
        'register_form' => [
            '1' => (('Yes')),
            '0' => (('No'))
        ],
    ];

    protected static $_fieldTypes = [];
    protected static $_importExportProfile = ['skip' => [],  'unique_key' => ['field_code']];

    protected $_oldTableFieldCode;
    protected $_oldTableFieldType;

    protected static $_fieldsCache = [];

    public function registerFieldType($type, $params)
    {
        static::$_fieldOptions['field_type'][$type] = $params['label'];
        static::$_fieldTypes[$type] = $params;
        return $this;
    }

    /**
     * @param string $key
     * @param string $fieldType
     *
     * @return FCom_Core_Model_Field[]
     */
    public function getAllFields($key = 'field_code', $fieldType = null)
    {
        $ft = $fieldType ?: 'ALL';
        if (!isset(static::$_fieldsCache[$ft][$key])) {
            $orm = $this->orm('f')->order_by_asc('field_name');
            if ($fieldType) {
                $orm->where('field_type', $fieldType);
            }
            static::$_fieldsCache[$ft][$key] = $orm->find_many_assoc($key);
        }
        return static::$_fieldsCache[$ft][$key];
    }

    /**
     * @param string $key
     * @param string $prop
     * @param string $fieldType
     * @return static|null
     */
    public function getField($key, $prop = 'field_code', $fieldType = null)
    {
        $fields = $this->getAllFields($fieldType);
        if (!$key) {
            return null;
        }
        if ($prop === 'field_code') {
            return !empty($fields[$key]) ? $fields[$key] : null;
        }
        /** @var static $field */
        foreach ($fields as $field) {
            if ($field->get($prop) === $key) {
                return $field;
            }
        }
        return null;
    }

    public function getFieldOptions($full = false)
    {
        return $this->FCom_Core_Model_FieldOption->getFieldOptions($this, $full, 'label');
    }

    /**
     * @param array $data
     * @return FCom_Core_Model_Field
     */
    public function addField($data)
    {
        $field = $this->load($this->BUtil->arrayMask($data, 'field_type,field_code'));
        if (!$field) {
            $field = $this->create($data)->save();
        } else {
            $field->set($data)->save();
        }
        static::$_fieldsCache[$field->get('field_code')] = $field;
        return $field;
    }

    /**
     * @param string $fieldType
     * @return array
     */
    public function getDropdowns($fieldType)
    {
        $fields = $this->getAllFields($fieldType);
        $result = [];
        foreach ($fields as $field) {
            if ($field->get('admin_input_type') === 'select') {
                $result[$field->id()] = [
                    'text' => $field->get('field_name'),
                    'data-code' => $field->get('field_code'),
                    'data-frontend-label' => $field->get('frontend_label')
                ];
            }
        }
        return $result;
    }

    /**
     * @param $code
     * @return mixed
     */
    public function getFrontendLabel($code)
    {
        $field = $this->getField($code);
        return $field ? $field->get('frontend_label') : null;
    }
}
