<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogFields_Model_Field
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
 * @property Sellvana_CatalogFields_Model_FieldOption $Sellvana_CatalogFields_Model_FieldOption
 */
class Sellvana_CatalogFields_Model_Field extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_field';

    protected static $_fieldOptions = [
        'field_type'      => [
            'product'     => 'Products',
        ],
        'table_field_type' => [
            'options'      => 'Options',
            'varchar'      => 'Short Text',
            'text'         => 'Long Text',
            'int'          => 'Integer',
            'decimal'      => 'Decimal',
            'date'         => 'Date',
            'datetime'     => 'Date/Time',
            'serialized'   => 'Serialized',
        ],
        'admin_input_type' => [
            'text'         => 'Text Line',
            'textarea'     => 'Text Area',
            'select'       => 'Drop down',
            'multiselect'  => 'Multiple Select',
            'boolean'      => 'Yes/No',
            'wysiwyg'      => 'WYSIWYG editor'
        ],
        'frontend_show'    => [
            '1'            => 'Yes',
            '0'            => 'No'
        ],
    ];

    protected static $_fieldDefaults = [
        'field_type' => 'product',
    ];

    protected static $_fieldTypes = [
        'product' => [
            'class' => 'Sellvana_CatalogFields_Model_ProductField',
        ],
    ];
    protected static $_importExportProfile = ['skip' => [],  'unique_key' => ['field_code',],  ];

    protected $_oldTableFieldCode;
    protected $_oldTableFieldType;

    protected static $_fieldsCache;

    /**
     * @return Sellvana_CatalogFields_Model_Field[]
     */
    public function getAllFields()
    {
        if (null === static::$_fieldsCache) {
            static::$_fieldsCache = $this->orm('f')->order_by_asc('field_name')->find_many_assoc('field_code');
        }
        return static::$_fieldsCache;
    }

    /**
     * @param string $key
     * @param string $prop
     * @return static|null
     */
    public function getField($key, $prop = 'field_code')
    {
        $fields = $this->getAllFields();
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
        return $this->Sellvana_CatalogFields_Model_FieldOption->getFieldOptions($this, $full);
    }

    /**
     * @param array $data
     * @return Sellvana_CatalogFields_Model_Field
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
     * @return array
     */
    public function getDropdowns()
    {
        $fields = $this->getAllFields();
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
