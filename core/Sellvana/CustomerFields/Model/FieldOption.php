<?php

/**
 * Class Sellvana_CustomerFields_Model_FieldOption
 *
 * @property int               $id
 * @property int               $field_id
 * @property string            $label
 * @property string            $locale
 * @property Sellvana_CustomerFields_Model_Field $Sellvana_CustomerFields_Model_Field
 */
class Sellvana_CustomerFields_Model_FieldOption extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_customer_field_option';
    protected static $_importExportProfile = [
        'unique_key' => ['field_id', 'label'],
        'related' => [
            'field_id' => 'Sellvana_CustomerFields_Model_Field.id',
        ],
    ];

    protected static $_optionsCache     = [];
    protected static $_allOptionsLoaded = false;

    public function getListAssocById($fieldId)
    {
        $result = [];
        /** @var Sellvana_CustomerFields_Model_FieldOption[] $options */
        $options = $this->orm()->where("field_id", $fieldId)->find_many();
        foreach ($options as $o) {
            $result[$o->label] = $o->label;
        }
        return $result;
    }
    public function getListAssoc()
    {
        $result = [];
        /** @var Sellvana_CustomerFields_Model_FieldOption[] $options */
        $options = $this->orm()->find_many();
        foreach ($options as $o) {
            $result[$o->field_id][] = $o->label;
        }
        return $result;
    }

    /**
     * @param bool|false $reload
     * @return $this
     */
    public function preloadAllFieldsOptions($reload = false)
    {
        if (!$reload && static::$_allOptionsLoaded) {
            return $this;
        }
        $options = $this->orm()->order_by_asc('field_id')->order_by_asc('label')->find_many();
        foreach ($options as $option) {
            static::$_optionsCache[$option->get('field_id')][$option->id()] = $option;
        }
        static::$_allOptionsLoaded = true;

        return $this;
    }

    /**
     * @return array
     */
    public function getAllFieldsOptions()
    {
        if(!static::$_optionsCache){
            $this->preloadAllFieldsOptions();
        }
        return static::$_optionsCache;
    }

    /**
     * @param int|string|Sellvana_CustomerFields_Model_Field $field
     * @param bool                                           $full
     * @param string                                         $idField
     * @param string                                         $labelField
     * @return Sellvana_CustomerFields_Model_FieldOption[]|null
     */
    public function getFieldOptions($field, $full = false, $idField = 'id', $labelField = 'label')
    {
        $fieldId = null;
        if (is_object($field)) {
            $fieldId = $field->id();
        } elseif (is_numeric($field)) {
            $fieldId = $field;
        } elseif (is_string($field)) {
            $field = $this->Sellvana_CustomerFields_Model_Field->getField($field);
            if (!$field) {
                return null;
            }
            $fieldId = $field->id();
        }
        if (empty(static::$_optionsCache[$fieldId])) {
            static::$_optionsCache[$fieldId] = $this->orm()->where('field_id', $fieldId)->order_by_asc('label')
                                                    ->find_many_assoc();
        }
        if ($full) {
            return static::$_optionsCache[$fieldId];
        } else {
            return $this->BUtil->arrayToOptions(static::$_optionsCache[$fieldId], $labelField, $idField);
        }
    }

}
