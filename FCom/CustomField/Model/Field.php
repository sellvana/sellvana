<?php

class FCom_CustomField_Model_Field extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_field';

    protected static $_fieldOptions = array(
        'field_type'       => array(
            'product' => 'Products',
        ),
        'table_field_type' => array(
            'varchar(255)'  => 'Short Text',
            'text'          => 'Long Text',
            'int(11)'       => 'Integer',
            'tinyint(3)'    => 'Tiny Int',
            'decimal(12,2)' => 'Decimal',
            'date'          => 'Date',
            'datetime'      => 'Date/Time',
            '_serialized'   => 'Serialized',
        ),
        'admin_input_type' => array(
            'text'        => 'Text Line',
            'textarea'    => 'Text Area',
            'select'      => 'Drop down',
            'multiselect' => 'Multiple Select',
            'boolean'     => 'Yes/No',
            'wysiwyg'     => 'WYSIWYG editor'
        ),
        'frontend_show'    => array(
            '1' => 'Yes',
            '0' => 'No'
        ),
    );

    protected static $_fieldTypes = array(
        'product' => array(
            'class' => 'FCom_CustomField_Model_ProductField',
        ),
    );
    protected static $_importExportProfile = array( 'skip' => array(),  'unique_key' => array( 'field_code', ),  );

    protected $_oldTableFieldCode;
    protected $_oldTableFieldType;

    protected static $_fieldsCache = array();

    public function tableName()
    {
        if ( empty( static::$_fieldTypes[ $this->field_type ] ) ) {
            return null;
        }
        $class = static::$_fieldTypes[ $this->field_type ][ 'class' ];
        return $class::table();
    }

    public static function fieldsInfo( $type, $keysOnly = false )
    {
        if ( empty( static::$_fieldsCache[ $type ] ) ) {
            $class = static::$_fieldTypes[ $type ][ 'class' ];
            $fields = BDb::ddlFieldInfo( $class::table() );
            unset( $fields[ 'id' ], $fields[ 'product_id' ] );
            static::$_fieldsCache[ $type ] = $fields;
        }
        return $keysOnly ? array_keys( static::$_fieldsCache[ $type ] ) : static::$_fieldsCache[ $type ];
    }

    public function onAfterLoad()
    {
        parent::onAfterLoad();
        $this->_oldTableFieldCode = $this->field_code;
        $this->_oldTableFieldType = $this->table_field_type;
    }

    public function addField( $data )
    {
        $field = static::load( BUtil::arrayMask( $data, 'field_type,field_code' ) );
        if ( !$field ) {
            $field = static::create( $data )->save();
        } else {
            $field->set( $data )->save();
        }
        return $field;
    }

    public function onBeforeSave()
    {
        if ( !parent::onBeforeSave() ) return false;

        if ( !$this->field_type ) $this->field_type = 'product';

        if ( $this->_oldTableFieldCode !== $this->field_code &&
            $this->field_type === '_serialized' && !empty( $this->_oldTableFieldCode )
        ) {
            $this->field_code = $this->_oldTableFieldCode; // TODO: disallow code change in UI
        }
        return true;
    }

    public function onAfterSave()
    {
        $fTable = $this->tableName();
        $fCode = preg_replace( '#([^0-9A-Za-z_])#', '', $this->field_code );
        $fType = preg_replace( '#([^0-9a-z\(\),])#', '', strtolower( $this->table_field_type ) );
        $field = BDb::ddlFieldInfo( $fTable, $this->field_code );
        $columnsUpdate = array();

        if ( $fType === '_serialized' ) {
            if ( $field ) {
                $columnsUpdate[ $fCode ] = 'DROP';
            } elseif ( $this->_oldTableFieldCode !== $fCode ) {
                //TODO: rename key name in all records??
            }
        } else {
            if ( !$field ) {
                $columnsUpdate[ $fCode ] = $fType;
            } elseif ( $this->_oldTableFieldCode !== $fCode ) {
                $columnsUpdate[ $this->_oldTableFieldCode ] = "RENAME {$fCode} {$fType}";
            }
        }
        if ( $columnsUpdate ) {
            BDb::ddlTableDef( $fTable, array( 'COLUMNS' => $columnsUpdate ) );
        }

        $this->_oldTableFieldCode = $this->field_code;
        $this->_oldTableFieldType = $this->table_field_type;
        //fix field code name
        if ( $this->field_code != $fCode ) {
            $this->field_code = $fCode;
            $this->save();
        }

        parent::onAfterSave();
    }

    public function onAfterDelete()
    {
        parent::onAfterDelete();
        if ( $this->table_field_type !== '_serialized' ) {
            BDb::ddlTableDef( $this->tableName(), array( 'COLUMNS' => array( $this->field_code => 'DROP' ) ) );
        }
    }

    public function products()
    {
        return FCom_Catalog_Model_Product::i()->orm( 'p' )->where_not_null( $this->field_code )->find_many();
    }

    public function getListAssoc()
    {
        $result = array();
        $cfList = $this->orm()->find_many();
        foreach ( $cfList as $cffield ) {
            $result[ $cffield->field_code ] = $cffield;
        }
        return $result;
    }

    public function getDropdowns()
    {
        $fields = BDb::many_as_array( $this->orm()->where( 'admin_input_type', 'select' )->find_many() );
        $res = array();
        foreach ( $fields as $field ) {
            $res[ $field[ 'id' ] ] = $field[ 'field_name' ];
        }
        return $res;
    }
}
