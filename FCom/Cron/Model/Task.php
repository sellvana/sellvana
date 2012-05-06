<?php

class FCom_Cron_Model_Task extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_cron_task';

    public static function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `cron_expr` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `last_start_dt` datetime DEFAULT NULL,
  `last_finish_dt` datetime DEFAULT NULL,
  `status` enum('pending','running','success','error','timeout') COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_error_msg` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `handle` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }
}