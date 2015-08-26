<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogFields_Model_FieldOption
 *
 * @property int $id
 * @property int $field_id
 * @property string $label
 * @property string $locale
 */
class Sellvana_CatalogFields_Model_FieldOption extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_field_option';
    protected static $_importExportProfile = [
        'unique_key' => ['field_id', 'label'],
        'related' => ['field_id' => 'Sellvana_CatalogFields_Model_Field.id']];

    public function getListAssocById($fieldId)
    {
        $result = [];
        /** @var Sellvana_CatalogFields_Model_FieldOption[] $options */
        $options = $this->orm()->where("field_id", $fieldId)->find_many();
        foreach ($options as $o) {
            $result[$o->id] = $o->label;
        }
        return $result;
    }
    public function getListAssoc()
    {
        $result = [];
        /** @var Sellvana_CatalogFields_Model_FieldOption[] $options */
        $options = $this->orm()->find_many();
        foreach ($options as $o) {
            $result[$o->field_id][] = $o->label;
        }
        return $result;
    }
}
