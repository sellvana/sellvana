<?php

class FCom_Wishlist_Model_WishlistItem extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_wishlist_items';
    protected static $_origClass = __CLASS__;

    protected $product;

    public function product()
    {
        if (!$this->product) {
            $this->product = $this->relatedModel('FCom_Catalog_Model_Product', $this->product_id);
        }
        return $this->product;
    }

    public static function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wishlist_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `wishlist_id` (`wishlist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}