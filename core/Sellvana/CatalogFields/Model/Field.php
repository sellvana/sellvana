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
 */
class Sellvana_CatalogFields_Model_Field extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_field';

    protected static $_fieldOptions = [
        'field_type'       => [
            'product' => 'Products',
        ],
        'table_field_type'  => [
            'varchar'       => 'Short Text',
            'text'          => 'Long Text',
            'int'           => 'Integer',
            'tinyint'       => 'Tiny Int',
            'decimal'       => 'Decimal',
            'date'          => 'Date',
            'datetime'      => 'Date/Time',
            '_serialized'   => 'Serialized',
        ],
        'table_field_columns'  => [
            'varchar'       => 'value_var',
            'text'          => 'value_text',
            'int'           => 'value_int',
            'tinyint'       => 'value_int',
            'decimal'       => 'value_dec',
            'date'          => 'value_date',
            'datetime'      => 'value_date',
            '_serialized'   => 'value_text',
        ],
        'admin_input_type' => [
            'text'        => 'Text Line',
            'textarea'    => 'Text Area',
            'select'      => 'Drop down',
            'multiselect' => 'Multiple Select',
            'boolean'     => 'Yes/No',
            'wysiwyg'     => 'WYSIWYG editor'
        ],
        'frontend_show'    => [
            '1' => 'Yes',
            '0' => 'No'
        ],
    ];

    protected static $_fieldTypes = [
        'product' => [
            'class' => 'Sellvana_CatalogFields_Model_ProductField',
        ],
    ];
    protected static $_importExportProfile = ['skip' => [],  'unique_key' => ['field_code',],  ];

    protected $_oldTableFieldCode;
    protected $_oldTableFieldType;

    protected static $_fieldsCache = [];

    /**
     * @param $type
     * @param bool $keysOnly
     * @return array
     */
    public function fieldsInfo($type, $keysOnly = false)
    {
        if (empty(static::$_fieldsCache[$type])) {
            $class = static::$_fieldTypes[$type]['class'];
            $fields = $this->BDb->ddlFieldInfo($class::table());
            unset($fields['id'], $fields['product_id']);
            static::$_fieldsCache[$type] = $fields;
        }
        return $keysOnly ? array_keys(static::$_fieldsCache[$type]) : static::$_fieldsCache[$type];
    }

    public function onAfterLoad()
    {
        parent::onAfterLoad();
        $this->_oldTableFieldCode = $this->field_code;
        $this->_oldTableFieldType = $this->table_field_type;
    }

    /**
     * @param array $data
     * @return Sellvana_CatalogFields_Model_Field
     * @throws BException
     */
    public function addField($data)
    {
        $field = $this->load($this->BUtil->arrayMask($data, 'field_type,field_code'));
        if (!$field) {
            $field = $this->create($data)->save();
        } else {
            $field->set($data)->save();
        }
        return $field;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if (!$this->field_type) $this->field_type = 'product';

        if ($this->_oldTableFieldCode !== $this->field_code &&
            $this->field_type === '_serialized' && !empty($this->_oldTableFieldCode)
        ) {
            $this->field_code = $this->_oldTableFieldCode; // TODO: disallow code change in UI
        }
        return true;
    }

    /**
     * @return array
     */
    public function products()
    {
        return $this->Sellvana_Catalog_Model_Product->orm('p')->where_not_null($this->field_code)->find_many();
    }

    /**
     * @return array
     */
    public function getListAssoc()
    {
        $result = [];
        $cfList = $this->orm()->find_many();
        foreach ($cfList as $cffield) {
            $result[$cffield->field_code] = $cffield;
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getDropdowns()
    {
        $fields = $this->BDb->many_as_array($this->orm()->where('admin_input_type', 'select')->find_many());
        $res = [];
        foreach ($fields as $field) {
            $res[$field['id']] = ['text' => $field['field_name'], 'data-code' => $field['field_code'], 'data-frontend-label' => $field['frontend_label']];
        }
        return $res;
    }

    /**
     * @param $code
     * @return mixed
     * @throws BException
     */
    public function getFrontendLabel($code)
    {
        $field = $this->load($code, 'field_code');
        return $field->get('frontend_label');
    }
}
