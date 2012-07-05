<?php

class FCom_Catalog_Model_ProductMedia extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_media';
    protected static $_origClass = __CLASS__;

    public function getUrl()
    {
        $row = BORM::for_table('fcom_media_library')->where('id', $this->file_id)->find_one();
        return BApp::baseUrl().$row->folder.'/'.$row->file_name;
    }
    public static function install()
    {
        $t = static::table();
        $prodTable = FCom_Catalog_Model_Product::table();
        $mediaTable = FCom_Core_Model_MediaLibrary::table();

        BDb::run("
CREATE TABLE IF NOT EXISTS {$t} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned DEFAULT NULL,
  `media_type` char(1) NOT NULL,
  `file_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  KEY `product_id__media_type` (`product_id`,`media_type`),
  CONSTRAINT `FK_{$t}_product` FOREIGN KEY (`product_id`) REFERENCES `{$prodTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_{$t}_file` FOREIGN KEY (`file_id`) REFERENCES `{$mediaTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        ");
    }
}