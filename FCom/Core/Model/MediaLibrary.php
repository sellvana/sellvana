<?php

class FCom_Core_Model_MediaLibrary extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_media_library';
    protected static $_origClass = __CLASS__;

    public function install()
    {
        BDb::run("
CREATE TABLE ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `folder` varchar(32) NOT NULL,
  `subfolder` varchar(32) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `data_json` text,
  PRIMARY KEY (`id`),
  KEY `folder_file` (`folder`,`file_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}