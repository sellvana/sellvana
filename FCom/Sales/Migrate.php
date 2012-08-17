<?php
class FCom_Sales_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        $tOrder = FCom_Sales_Model_Order::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tOrder} (
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

        $tItem = FCom_Sales_Model_OrderItem::table();
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
