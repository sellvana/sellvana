<?php

/**
 * Class FCom_Core_Model_FieldOption
 *
 * @property int $id
 * @property int $field_id
 * @property string $label
 * @property string $locale
 *
 * @property FCom_Core_Model_Field $FCom_Core_Model_Field
 */
class FCom_Core_Model_FieldOption extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_field_option';

    protected static $_importExportProfile = [
        'unique_key' => ['field_id', 'label'],
        'related' => ['field_id' => 'FCom_Core_Model_Field.id'],
    ];

    protected static $_fieldDefaults = [
        'locale' => '_',
    ];

    protected static $_optionsCache = [];
    protected static $_allOptionsLoaded = false;

    /**
     * @param int|string|FCom_Core_Model_Field $field
     * @param bool $full
     * @param string $idField
     * @param string $labelField
     * @return FCom_Core_Model_FieldOption[]|null
     */
    public function getFieldOptions($field, $full = false, $idField = 'id', $labelField = 'label')
    {
        if (is_object($field)) {
            $fieldId = $field->id();
        } elseif (is_numeric($field)) {
            $fieldId = $field;
        } elseif (is_string($field)) {
            $field = $this->FCom_Core_Model_Field->getField($field);
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

    public function getAllFieldsOptions()
    {
        return static::$_optionsCache;
    }

    public function preloadAllFieldsOptions($reload = false)
    {
        if (!$reload && static::$_allOptionsLoaded) {
            return $this;
        }
        $options = $this->orm()->order_by_asc('field_id')->order_by_asc('label')->find_many();
        $this->updateOptionsCache($options);
        static::$_allOptionsLoaded = true;
        return $this;
    }

    public function updateOptionsCache(array $options)
    {
        foreach ($options as $option) {
            static::$_optionsCache[$option->get('field_id')][$option->id()] = $option;
        }
        return $this;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();
        $this->updateOptionsCache([$this]);
    }

    /**
     * @return array
     * @deprecated
     */
    public function getListAssoc()
    {
        $result = [];
        /** @var FCom_Core_Model_FieldOption[] $options */
        $options = $this->orm()->find_many();
        foreach ($options as $o) {
            $result[$o->field_id][] = $o->label;
        }
        return $result;
    }
}
