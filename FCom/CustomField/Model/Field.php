<?php

class FCom_CustomField_Model_Field extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_field';

    protected static $_fieldOptions = array(
        'field_type' => array(
            'product' => 'Products',
        ),
        'table_field_type' => array(
            'date' => 'Date',
            'datetime' => 'Date/Time',
            'decimal(12,4)' => 'Decimal',
            'int(11)' => 'Integer',
            'tinyint(3)' => 'Tiny Int',
            'text' => 'Long Text',
            'varchar(255)' => 'Short Text',
        ),
        'admin_input_type' => array(
            'text' => 'Text Line',
            'textarea' => 'Text Area',
            'select' => 'Drop down',
            'boolean' => 'Yes/No',
        ),
    );

    protected static $_fieldTypes = array(
        'product' => array(
            'class' => 'FCom_CustomField_Model_ProductField',
        ),
    );

    protected $_oldTableFieldCode;

    protected static $_fieldsCache = array();

    public function tableName()
    {
        if (empty(static::$_fieldTypes[$this->field_type])) {
            return null;
        }
        $class = static::$_fieldTypes[$this->field_type]['class'];
        return $class::table();
    }

    public static function fieldsInfo($type, $keysOnly=false)
    {
        if (empty(static::$_fieldsCache[$type])) {
            $class = static::$_fieldTypes[$type]['class'];
            $fields = BDb::ddlFieldInfo($class::table());
            unset($fields['id'], $fields['product_id']);
            static::$_fieldsCache[$type] = $fields;
        }
        return $keysOnly ? array_keys(static::$_fieldsCache[$type]) : static::$_fieldsCache[$type];
    }

    public function afterLoad()
    {
        $this->_oldTableFieldCode = $this->field_code;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->field_type) $this->field_type = 'product';
        return true;
    }

    public function afterSave()
    {
        $fTable = $this->tableName();
        $fCode = preg_replace('#([^0-9a-z_])#', '', $this->field_code);
        $fType = preg_replace('#([^0-9a-z\(\),])#', '', $this->table_field_type);
        $field = BDb::ddlFieldInfo($fTable, $this->field_code);
        if (!$field) {
            BDb::run("ALTER TABLE {$fTable} ADD COLUMN {$fCode} {$fType}");
        } elseif ($field->Type!=$fType || $this->_oldTableFieldCode!=$fCode) {
            BDb::run("ALTER TABLE {$fTable} CHANGE COLUMN {$this->_oldTableFieldCode} {$fCode} {$fType}");
        }
    }

    public function afterDelete()
    {
        BDb::run("ALTER TABLE {$this->tableName()} DROP COLUMN {$this->field_code}");
    }
}