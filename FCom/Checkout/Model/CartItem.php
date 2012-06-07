<?php

class FCom_Checkout_Model_CartItem extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cart_item';

    public $product;

    public function product()
    {
        if (!$this->product) {
            $this->product = $this->relatedModel('FCom_Catalog_Model_Product', $this->product_id);
        }
        return $this->product;
    }

    public function rowTotal()
    {
        return $this->price;
    }

    public static function install()
    {
        $tCartItem = static::table();
        $tCart = FCom_Checkout_Model_Cart::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tCartItem} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `qty` decimal(12,4) DEFAULT NULL,
  `price` decimal(12,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_id` (`cart_id`,`product_id`),
  CONSTRAINT `FK_{$tCartItem}_cart` FOREIGN KEY (`cart_id`) REFERENCES {$tCart} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}

