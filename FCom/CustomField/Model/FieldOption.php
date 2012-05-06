<?php

class FCom_CustomField_Model_FieldOption extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_field_option';

    public static function install()
    {
        $tField = FCom_CustomField_Model_Field::table();
        $tFieldOption = static::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tFieldOption} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(10) unsigned NOT NULL,
  `label` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_id__label` (`field_id`,`label`),
  CONSTRAINT `FK_{$tFieldOption}_field` FOREIGN KEY (`field_id`) REFERENCES {$tField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}
