<?php

class FCom_Customer_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
        BMigrate::upgrade('0.1.1', '0.1.2', array($this, 'upgrade_0_1_2'));
        BMigrate::upgrade('0.1.2', '0.1.3', array($this, 'upgrade_0_1_3'));
    }

    public function install()
    {
        $tCustomer = FCom_Customer_Model_Customer::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tCustomer} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
            `firstname` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
            `lastname` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
            `password_hash` text COLLATE utf8_unicode_ci,
            `default_shipping_id` int(11) unsigned DEFAULT NULL,
            `default_billing_id` int(11) unsigned DEFAULT NULL,
            `create_dt` datetime NOT NULL,
            `update_dt` datetime NOT NULL,
            `last_login` datetime DEFAULT NULL,
            `token` varchar(20) DEFAULT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");

        $tCustomer = FCom_Customer_Model_Customer::table();
        $tAddress = FCom_Customer_Model_Address::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tAddress} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) unsigned NOT NULL,
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
            CONSTRAINT `FK_{$tAddress}_customer` FOREIGN KEY (`customer_id`) REFERENCES {$tCustomer} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
            /*
        ALTER TABLE {$tCustomer}
        ADD CONSTRAINT `FK_{$tCustomer}_billing` FOREIGN KEY (`default_billing_id`) REFERENCES {$tAddress} (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
        ADD CONSTRAINT `FK_{$tCustomer}_shipping` FOREIGN KEY (`default_shipping_id`) REFERENCES {$tAddress} (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
        */
    }

    public function upgrade_0_1_1()
    {
        $tAddress = FCom_Customer_Model_Address::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tAddress, "lat")) {
            return;
        }
        try {
            BDb::run("
                ALTER TABLE {$tAddress}
                ADD COLUMN `lat` DECIMAL(15,10) NULL,
                ADD COLUMN `lng` DECIMAL(15,10) NULL;
            ");
        } catch (Exception $e) {}
    }

    public function upgrade_0_1_2()
    {
        $tCustomer = FCom_Customer_Model_Customer::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tCustomer, "payment_method")) {
            return;
        }
        try {
            BDb::run("
                ALTER TABLE {$tCustomer}
                    ADD `payment_method` VARCHAR( 20 ) NOT NULL,
                    ADD `payment_details` TEXT NOT NULL
                    ;
            ");
        } catch (Exception $e) {}
    }
    
    public function upgrade_0_1_3()
    {
        BDb::ddlTableDef(FCom_Customer_Model_Address::table(), array(
            'COLUMNS' => array(
                'state' => 'RENAME region varchar(50)',
                'zip' => 'RENAME postcode varchar(20)',
            ),
        ));
    }
}