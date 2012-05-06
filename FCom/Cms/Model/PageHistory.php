<?php

class FCom_Cms_Model_PageHistory extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_page_history';
    protected static $_origClass = __CLASS__;

    public static function install()
    {
        $tPageHistory = static::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tPageHistory} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned NOT NULL,
  `version` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned null,
  `username` varchar(50) COLLATE utf8_unicode_ci NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `comments` text COLLATE utf8_unicode_ci NOT NULL,
  `ts` datetime not null,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }
}
