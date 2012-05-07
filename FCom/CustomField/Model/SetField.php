<?php

class FCom_CustomField_Model_SetField extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_fieldset_field';

    public static function install()
    {
        $tSet = FCom_CustomField_Model_Set::table();
        $tField = FCom_CustomField_Model_Field::table();
        $tSetField = static::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tSetField} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `set_id` int(10) unsigned NOT NULL,
  `field_id` int(10) unsigned NOT NULL,
  `position` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `set_id__field_id` (`set_id`,`field_id`),
  KEY `set_id__position` (`set_id`,`position`),
  CONSTRAINT `FK_{$tSetField}_field` FOREIGN KEY (`field_id`) REFERENCES {$tField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_{$tSetField}_set` FOREIGN KEY (`set_id`) REFERENCES {$tSet} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8;
        ");
    }
}
