<?php

class FCom_Admin_Model_Personalize extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_admin_personalize';

    public static function install()
    {
        $tPersonalize = static::table();
        $tUser = FCom_Admin_Model_User::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tPersonalize} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `data_json` text,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_{$tPersonalize}_user` FOREIGN KEY (`user_id`) REFERENCES {$tUser} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}