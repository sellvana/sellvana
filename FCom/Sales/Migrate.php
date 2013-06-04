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
        BMigrate::upgrade('0.1.4', '0.1.5', array($this, 'upgrade_0_1_5'));
        BMigrate::upgrade('0.1.5', '0.1.6', array($this, 'upgrade_0_1_6'));
        BMigrate::upgrade('0.1.6', '0.1.7', array($this, 'upgrade_0_1_7'));
        BMigrate::upgrade('0.1.7', '0.1.8', array($this, 'upgrade_0_1_8'));
        BMigrate::upgrade('0.1.8', '0.1.9', array($this, 'upgrade_0_1_9'));
        BMigrate::upgrade('0.1.9', '0.1.10', array($this, 'upgrade_0_1_10'));
        BMigrate::upgrade('0.1.10', '0.2.0', array($this, 'upgrade_0_2_0'));
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
        $tAddress = FCom_Sales_Model_OrderAddress::table();
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

    public function upgrade_0_1_5()
    {
        $tOrder = FCom_Sales_Model_Order::table();
        BDb::run("
            ALTER TABLE {$tOrder} ADD `shipping_service_title` varchar(100) not null default ''
        ");
    }

    public function upgrade_0_1_6()
    {
        $tStatus = FCom_Sales_Model_OrderStatus::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tStatus} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL DEFAULT '' ,
            `code` varchar(50) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`)
            )
        ");
    }

    public function upgrade_0_1_7()
    {
        $tStatus = FCom_Sales_Model_OrderStatus::table();
        BDb::run("
            insert into {$tStatus}(id,name,code) values(1, 'New', 'new'),(2,'Pending','pending'),(3,'Paid','paid')
        ");
    }

    public function upgrade_0_1_8()
    {
        $tOrder = FCom_Sales_Model_Order::table();
        BDb::run("
            ALTER TABLE {$tOrder} ADD `status_id` int(11) not null default 0
        ");
    }

    public function upgrade_0_1_9()
    {
        $tOrder = FCom_Sales_Model_Order::table();
        BDb::run("
            UPDATE  {$tOrder} SET `status_id` = 1 where status = 'new';
            UPDATE  {$tOrder} SET `status_id` = 2 where status = 'pending';
            UPDATE  {$tOrder} SET `status_id` = 3 where status = 'paid';
        ");
    }
    
    
    public function upgrade_0_1_10()
    {
        BDb::ddlTableDef(FCom_Sales_Model_OrderAddress::table(), array(
            'COLUMNS' => array(
                'state' => 'RENAME region varchar(50)',
                'zip' => 'RENAME postcode varchar(20)',
            ),
        ));
    }

    public function upgrade_0_2_0()
    {

        $tCart = FCom_Sales_Model_Cart::table();
        $tCartItem = FCom_Sales_Model_CartItem::table();
        $tAddress = FCom_Sales_Model_CartAddress::table();

        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tCart} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `company_id` int(10) unsigned DEFAULT NULL,
            `location_id` int(10) unsigned DEFAULT NULL,
            `user_id` int(10) unsigned NOT NULL,
            `description` varchar(255) DEFAULT NULL,
            `sort_order` int(11) DEFAULT NULL,
            `item_qty` decimal(12,4) NOT NULL DEFAULT '0.0000',
            `item_num` smallint(6) unsigned NOT NULL DEFAULT '0',
            `subtotal` decimal(12,4) NOT NULL DEFAULT '0.0000',
            `session_id` varchar(100) DEFAULT NULL,
            `shipping_method` VARCHAR( 50 ) NOT NULL ,
            `shipping_price` DECIMAL( 10, 2 ) NOT NULL ,
            `shipping_service` CHAR( 2 ) NOT NULL,
            `payment_method` VARCHAR( 50 ) NOT NULL ,
            `payment_details` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            `discount_code` VARCHAR( 50 ) NOT NULL,
            `calc_balance` DECIMAL( 10, 2 ) NOT NULL ,
            `totals_json` TEXT NOT NULL,
            `status` ENUM( 'new', 'finished' ) NOT NULL DEFAULT 'new',
            `create_dt` DATETIME NULL,
            `update_dt` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `NewIndex1` (`session_id`),
            UNIQUE KEY `user_id` (`user_id`,`description`,`session_id`),
            KEY `company_id` (`company_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

            CREATE TABLE IF NOT EXISTS {$tCartItem} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `cart_id` int(10) unsigned DEFAULT NULL,
            `product_id` int(10) unsigned DEFAULT NULL,
            `qty` decimal(12,4) DEFAULT NULL,
            `price` decimal(12,4) NOT NULL DEFAULT '0.0000',
            `rowtotal` DECIMAL(12,4) NULL,

            `promo_id_buy` VARCHAR(50) NOT NULL,
            `promo_id_get` INT(10) UNSIGNED NOT NULL,
            `promo_qty_used` decimal(12,4) DEFAULT NULL,
            `promo_amt_used` decimal(12,4) DEFAULT NULL,

            `create_dt` DATETIME NOT NULL,
            `update_dt` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `cart_id` (`cart_id`,`product_id`),
            CONSTRAINT `FK_{$tCartItem}_cart` FOREIGN KEY (`cart_id`) REFERENCES {$tCart} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

            CREATE TABLE IF NOT EXISTS {$tAddress} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `cart_id` int(11) unsigned NOT NULL,
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
            `email` VARCHAR( 100 ) NOT NULL,
            `create_dt` datetime NOT NULL,
            `update_dt` datetime NOT NULL,
            `lat` decimal(15,10) DEFAULT NULL,
            `lng` decimal(15,10) DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tAddress}_cart` FOREIGN KEY (`cart_id`) REFERENCES {$tCart} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }
}
