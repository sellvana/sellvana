<?php

class FCom_Customer_Model_Address extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_customer_address';
    protected static $_origClass = __CLASS__;

    public function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `firstname` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attn` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street1` text COLLATE utf8_unicode_ci NOT NULL,
  `street2` text COLLATE utf8_unicode_ci,
  `street3` text COLLATE utf8_unicode_ci,
  `city` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `county` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `county_id` int(11) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `create_dt` datetime NOT NULL,
  `update_dt` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }
}