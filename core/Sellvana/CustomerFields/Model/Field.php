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
class Sellvana_CustomerFields_Model_Field extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table     = 'fcom_customer_field';

    protected static $_fieldOptions = [
        'field_type' => [
            'customer' => 'Customer',
        ],
        'table_field_type' => [
            'varchar(255)' => 'Short Text',
            'text' => 'Long Text',
            'int(11)' => 'Integer',
            'tinyint(3)' => 'Tiny Int',
            'decimal(12,2)' => 'Decimal',
            'date' => 'Date',
            'datetime' => 'Date/Time',
            '_serialized' => 'Serialized',
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
    ];

    protected static $_fieldTypes = [
        'customer' => [
            'class' => 'Sellvana_CustomerFields_Model_CustomerField',
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
     * @param      $type
     * @param bool $keysOnly
     * @return array
     */
    public function fieldsInfo($type, $keysOnly = false)
    {
        if (empty(static::$_fieldsCache[$type])) {
            $class  = static::$_fieldTypes[$type]['class'];
            $fields = $this->BDb->ddlFieldInfo($class::table());
            unset($fields['id'], $fields['customer_id']);
            static::$_fieldsCache[$type] = $fields;
        }

        return $keysOnly? array_keys(static::$_fieldsCache[$type]): static::$_fieldsCache[$type];
    }

    /**
     * @return array
     */
    public function getListAssoc()
    {
        $result = [];
        $cfList = $this->orm()->find_many();
        /** @var self $cffield */
        foreach ($cfList as $cffield) {
            $result[$cffield->field_code] = $cffield;
        }

        return $result;
    }

    public function tableName() {
        if (empty(static::$_fieldTypes[$this->field_type])) {
            return null;
        }
        $class = static::$_fieldTypes[$this->field_type]['class'];
        return $class::table();
    }
}
