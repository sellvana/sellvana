<?php

/**
 * Class Sellvana_MultiLanguage_Model_Translation
 *
 * @property int $id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $locale
 * @property string $data_serialized
 * @property string $field
 * @property string $value
 */
class Sellvana_MultiLanguage_Model_Translation extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_multilanguage_translation';
    static protected $_origClass = __CLASS__;
}
