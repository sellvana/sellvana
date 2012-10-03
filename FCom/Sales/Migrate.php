<?php
class FCom_Sales_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
        BMigrate::upgrade('0.1.1', '0.1.2', array($this, 'upgrade_0_1_2'));
        BMigrate::upgrade('0.1.2', '0.1.3', array($this, 'upgrade_0_1_3'));
        BMigrate::upgrade('0.1.3', '0.1.4', array($this, 'upgrade_0_1_4'));
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

    public function upgrade_0_1_1()
    {
        $tOrder = FCom_Sales_Model_Order::table();
        $tAddress = FCom_Sales_Model_Address::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tAddress} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `order_id` int(11) unsigned NOT NULL,
            `atype` ENUM( 'shipping', 'billing' ) NOT NULL DEFAULT 'shipping',
            `firstname` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `lastname` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `attn` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `street1` text COLLATE utf8_unicode_ci NOT NULL,
            `street2` text COLLATE utf8_unicode_ci,
            `street3` text COLLATE utf8_unicode_ci,
            `city` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
            `state` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `zip` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
            `country` char(2) COLLATE utf8_unicode_ci NOT NULL,
            `phone` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `fax` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `create_dt` datetime NOT NULL,
            `update_dt` datetime NOT NULL,
            `lat` decimal(15,10) DEFAULT NULL,
            `lng` decimal(15,10) DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tAddress}_cart` FOREIGN KEY (`order_id`) REFERENCES {$tOrder} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

    public function upgrade_0_1_2()
    {
        $tOrder = FCom_Sales_Model_Order::table();
        BDb::run("
            ALTER TABLE {$tOrder} ADD COLUMN created_dt datetime NULL,
            ADD COLUMN purchased_dt datetime NULL;
        ");
    }

    public function upgrade_0_1_3()
    {
        $tOrder = FCom_Sales_Model_Order::table();
        BDb::run("
            ALTER TABLE {$tOrder} ADD COLUMN gt_base decimal(10,2) NOT NULL;
        ");
    }

    public function upgrade_0_1_4()
    {
        $tOrder = FCom_Sales_Model_Order::table();
        BDb::run("
            ALTER TABLE {$tOrder} MODIFY `status` enum('new', 'paid', 'pending') not null default 'new'
        ");
    }
}
