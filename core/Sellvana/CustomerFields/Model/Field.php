<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomerFields_Model_Field
 *
 * @property int $id
 * @property string $field_type (product|will be add more value)
 * @property string $field_code
 * @property string $field_name
 * @property string $table_field_type
 * @property string $admin_input_type
 * @property string $frontend_label
 * @property int    $frontend_show
 * @property string $config_json
 * @property int    $sort_order
 * @property string $facet_select (No|Exclusive|Inclusive)
 * @property int    $system
 * @property int    $multilanguage
 * @property string $validation
 * @property int    $required
 *
 * DI
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_CustomerFields_Model_FieldOption $Sellvana_CustomerFields_Model_FieldOption
 */
class Sellvana_CustomerFields_Model_Field extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table     = 'fcom_customer_field';

    protected static $_fieldOptions = [
        'field_type' => [
            'customer' => 'Customer',
        ],
        'table_field_type' => [
            'options'      => 'Options',
            'varchar'      => 'Short Text',
            'text'         => 'Long Text',
            'int'          => 'Integer',
            'tinyint'      => 'Tiny Integer',
            'decimal'      => 'Decimal',
            'date'         => 'Date',
            'datetime'     => 'Date/Time',
            'serialized'   => 'Serialized',
        ],
        'admin_input_type' => [
            'text' => 'Text Line',
            'textarea' => 'Text Area',
            'select' => 'Drop down',
            'multiselect' => 'Multiple Select',
            'boolean' => 'Yes/No',
            'wysiwyg' => 'WYSIWYG editor'
        ],
        'frontend_show' => [
            '1' => 'Yes',
            '0' => 'No'
        ],
        'account_edit' => [
            '1' => 'Yes',
            '0' => 'No'
        ],
        'register_form' => [
            '1' => 'Yes',
            '0' => 'No'
        ],
    ];

    protected static $_fieldDefaults = [
        'field_type' => 'customer',
    ];
    protected static $_fieldTypes = [
        'customer' => [
            'class' => 'Sellvana_CustomerFields_Model_CustomerFieldData',
        ],
    ];
    protected static $_importExportProfile = [
        'skip' => [],
        'unique_key' => ['field_code'],
    ];

    protected $_oldTableFieldCode;
    protected $_oldTableFieldType;

    protected static $_fieldsCache = [];

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
            return !empty($fields[$key])? $fields[$key]: null;
        }
        /** @var static $field */
        foreach ($fields as $field) {
            if ($field->get($prop) === $key) {
                return $field;
            }
        }

        return null;
    }

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

    public function tableName() {
        if (empty(static::$_fieldTypes[$this->field_type])) {
            return null;
        }
        $class = static::$_fieldTypes[$this->field_type]['class'];
        return $class::table();
    }


    //public function onAfterLoad()
    //{
    //    parent::onAfterLoad();
    //    $this->_oldTableFieldCode = $this->field_code;
    //    $this->_oldTableFieldType = $this->table_field_type;
    //}

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

    //public function onBeforeSave()
    //{
    //    if (!parent::onBeforeSave()) {
    //        return false;
    //    }
    //
    //    if (!$this->field_type) {
    //        $this->field_type = 'customer';
    //    }
    //
    //    if ($this->_oldTableFieldCode !== $this->field_code &&
    //        $this->field_type === '_serialized' && !empty($this->_oldTableFieldCode)
    //    ) {
    //        $this->field_code = $this->_oldTableFieldCode; // TODO: disallow code change in UI
    //    }
    //
    //    return true;
    //}

    //public function onAfterSave()
    //{
    //    $fTable        = $this->tableName();
    //    $fCode         = preg_replace('#([^0-9A-Za-z_])#', '', $this->field_code);
    //    $fType         = preg_replace('#([^0-9a-z\(\),])#', '', strtolower($this->table_field_type));
    //    $field         = $this->BDb->ddlFieldInfo($fTable, $this->field_code);
    //    $columnsUpdate = [];
    //
    //    if ($fType === '_serialized') {
    //        if ($field) {
    //            $columnsUpdate[$fCode] = 'DROP';
    //        } elseif ($this->_oldTableFieldCode !== $fCode) {
    //            //TODO: rename key name in all records??
    //        }
    //    } else {
    //        if (!$field) {
    //            $columnsUpdate[$fCode] = $fType;
    //        } elseif ($this->_oldTableFieldCode !== $fCode) {
    //            $columnsUpdate[$this->_oldTableFieldCode] = "RENAME {$fCode} {$fType}";
    //        }
    //    }
    //    if ($columnsUpdate) {
    //        $this->BDb->ddlTableDef($fTable, [BDb::COLUMNS => $columnsUpdate]);
    //    }
    //
    //    $this->_oldTableFieldCode = $this->field_code;
    //    $this->_oldTableFieldType = $this->table_field_type;
    //    //fix field code name
    //    if ($this->field_code != $fCode) {
    //        $this->field_code = $fCode;
    //        $this->save();
    //    }
    //
    //    parent::onAfterSave();
    //}

    //public function onAfterDelete()
    //{
    //    parent::onAfterDelete();
    //    if ($this->table_field_type !== '_serialized') {
    //        $this->BDb->ddlTableDef($this->tableName(), [BDb::COLUMNS => [$this->field_code => BDb::DROP]]);
    //    }
    //}

    /**
     * @return array
     */
    public function customers()
    {
        return $this->Sellvana_Customer_Model_Customer->orm('c')->where_not_null($this->field_code)->find_many();
    }


    /**
     * @return array
     */
    public function getDropdowns()
    {
        $fields = $this->getAllFields();
        $res    = [];
        foreach ($fields as $field) {
            if ($field->get('admin_input_type') === 'select') {
                $result[$field->id()] = [
                    'text'                => $field->get('field_name'),
                    'data-code'           => $field->get('field_code'),
                    'data-frontend-label' => $field->get('frontend_label')
                ];
            }        }

        return $res;
    }

    public function getOptions()
    {
        $options = [];
        $input   = $this->get('admin_input_type');
        if($input == 'select' || $input == 'multiselect'){
            $options = $this->Sellvana_CustomerFields_Model_FieldOption->getListAssocById($this->id());
        }
        return $options;
    }

    /**
     * @param $code
     * @return mixed
     * @throws BException
     */
    public function getFrontendLabel($code)
    {
        $field = $this->getField($code);

        return $field? $field->get('frontend_label'): null;
    }
}
