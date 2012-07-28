<?php

class FCom_Sales_Model_OrderItem extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_item';
    protected static $_origClass = __CLASS__;

    /**
    * Fallback singleton/instance factory
    *
    * @param bool $new if true returns a new instance, otherwise singleton
    * @param array $args
    * @return FCom_Sales_Model_OrderItem
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(get_called_class(), $args, !$new);
    }

    public function add($data)
    {
        BPubSub::i()->fire(__CLASS__.'.add', array('orderItem'=>$data));
        return $this->create($data)->save();
    }

    public function isItemExist($orderId, $product_id)
    {
        return $this->orm()->where("order_id", $orderId)
                        ->where("product_id", $product_id)->find_one();
    }

    public static function install()
    {
        $tItem = static::table();
        $tOrder = FCom_Sales_Model_Order::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tItem} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `qty` int(10) unsigned DEFAULT NULL,
  `total` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `product_info` text,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_{$tItem}_cart` FOREIGN KEY (`order_id`) REFERENCES {$tOrder} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}