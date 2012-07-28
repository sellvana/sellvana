<?php

class FCom_Sales_Model_Order extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order';
    protected static $_origClass = __CLASS__;

    /**
    * Fallback singleton/instance factory
    *
    * @param bool $new if true returns a new instance, otherwise singleton
    * @param array $args
    * @return FCom_Sales_Model_Order
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(get_called_class(), $args, !$new);
    }

    public function billing()
    {
        return FCom_Checkout_Model_Address::i()->orm('a')
                ->where('cart_id', $this->cart_id)->where('atype', 'billing')->find_one();
    }

    public function add($data)
    {
        $data['status'] = 'new';
        BPubSub::i()->fire(__CLASS__.'.add', array('order'=>$data));
        return $this->create($data)->save();
    }

    public function paid()
    {
        $this->set('status', 'paid')->save();
    }

    public static function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `cart_id` int(10) unsigned NOT NULL,
  `status` enum('new', 'paid') not null default 'new',
  `item_qty` int(10) unsigned NOT NULL,
  `subtotal` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `shipping_method` varchar(50) NOT NULL,
  `shipping_service` char(2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_details` text NOT NULL,
  `discount_code` varchar(50) NOT NULL,
  `tax` varchar(50) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `totals_json` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY cart_id (`cart_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}