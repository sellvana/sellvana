<?php
class FCom_Checkout_Migrate extends BClass
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
    }

    public function install()
    {
        $tCart = FCom_Checkout_Model_Cart::table();
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
            PRIMARY KEY (`id`),
            UNIQUE KEY `NewIndex1` (`session_id`),
            UNIQUE KEY `user_id` (`user_id`,`description`,`session_id`),
            KEY `company_id` (`company_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tCartItem = FCom_Checkout_Model_CartItem::table();
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

    public function upgrade_0_1_1()
    {
        $tCartItem = FCom_Checkout_Model_CartItem::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tCartItem, 'price')) {
            return;
        }
        BDb::run("
            alter table {$tCartItem} add  `price` decimal(12,4) NOT NULL DEFAULT '0.0000'
        ");
    }

    public function upgrade_0_1_2()
    {
        $tCart = FCom_Checkout_Model_Cart::table();
        $tAddress = FCom_Checkout_Model_Address::table();
        BDb::run("
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
            `create_dt` datetime NOT NULL,
            `update_dt` datetime NOT NULL,
            `lat` decimal(15,10) DEFAULT NULL,
            `lng` decimal(15,10) DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tAddress}_cart` FOREIGN KEY (`cart_id`) REFERENCES {$tCart} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

    public function upgrade_0_1_3()
    {
        $tCart = FCom_Checkout_Model_Cart::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tCart, "shipping_method")) {
            return;
        }
        BDb::run("
            ALTER TABLE {$tCart} ADD `shipping_method` VARCHAR( 50 ) NOT NULL ,
            ADD `shipping_price` DECIMAL( 10, 2 ) NOT NULL ,
            ADD `payment_method` VARCHAR( 50 ) NOT NULL ,
            ADD `payment_details` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            ADD `discount_code` VARCHAR( 50 ) NOT NULL,
            ADD `calc_balance` DECIMAL( 10, 2 ) NOT NULL ,
            ADD `totals_json` TEXT NOT NULL "
        );
    }

    public function upgrade_0_1_4()
    {
        $tCart = FCom_Checkout_Model_Cart::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tCart, "shipping_service")){
            return;
        }
        BDb::run("
            ALTER TABLE {$tCart} ADD `shipping_service` CHAR( 2 ) NOT NULL AFTER `shipping_price`"
        );
    }

    public function upgrade_0_1_5()
    {
        $tCart = FCom_Checkout_Model_Cart::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tCart, "status")){
            return;
        }
        BDb::run("
            ALTER TABLE {$tCart} ADD `status` ENUM( 'new', 'finished' ) NOT NULL DEFAULT 'new'"
        );
    }

    public function upgrade_0_1_6()
    {
        $tAddress = FCom_Checkout_Model_Address::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tAddress, "email")){
            return;
        }
        BDb::run("
            ALTER TABLE {$tAddress} ADD `email` VARCHAR( 100 ) NOT NULL "
        );
    }

    public function upgrade_0_1_7()
    {
        $tCartItem = FCom_Checkout_Model_CartItem::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tCartItem, "rowtotal")){
            return;
        }
        BDb::run("
            ALTER TABLE {$tCartItem} ADD COLUMN `rowtotal` DECIMAL(12,4) NULL AFTER `price`,
                ADD COLUMN `create_dt` DATETIME NOT NULL AFTER `rowtotal`,
                ADD COLUMN `update_dt` DATETIME NOT NULL AFTER `create_dt`;
        ");
    }

}
