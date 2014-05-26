<?php

class FCom_Customer_Migrate extends BClass
{
    public function install__0_1_10()
    {
        $tCustomer = FCom_Customer_Model_Customer::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tCustomer} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `email` varchar(255)  NOT NULL,
            `firstname` varchar(50)  NOT NULL,
            `lastname` varchar(50)  NOT NULL,
            `password_hash` text ,
            `default_shipping_id` int(11) unsigned DEFAULT NULL,
            `default_billing_id` int(11) unsigned DEFAULT NULL,
            `create_at` datetime NOT NULL,
            `update_at` datetime NOT NULL,
            `last_login` datetime DEFAULT NULL,
            `token` varchar(20) DEFAULT NULL,
            `payment_method` varchar(20)  DEFAULT NULL,
            `payment_details` text ,
            `status` enum('review','active','disabled') NOT NULL DEFAULT 'review',
            `password_session_token` varchar(16),
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
        ");

        $tCustomer = FCom_Customer_Model_Customer::table();
        $tAddress = FCom_Customer_Model_Address::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tAddress} (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `customer_id` int(11) unsigned NOT NULL,
              `email` varchar(255)  NOT NULL,
              `firstname` varchar(50)  DEFAULT NULL,
              `lastname` varchar(50)  DEFAULT NULL,
              `middle_initial` varchar(2)  DEFAULT NULL,
              `prefix` varchar(10)  DEFAULT NULL,
              `suffix` varchar(10)  DEFAULT NULL,
              `company` varchar(50)  DEFAULT NULL,
              `attn` varchar(50)  DEFAULT NULL,
              `street1` text  NOT NULL,
              `street2` text ,
              `street3` text ,
              `city` varchar(50)  NOT NULL,
              `region` varchar(50)  DEFAULT NULL,
              `postcode` varchar(20)  DEFAULT NULL,
              `country` char(2)  NOT NULL,
              `phone` varchar(50)  DEFAULT NULL,
              `fax` varchar(50)  DEFAULT NULL,
              `create_at` datetime NOT NULL,
              `update_at` datetime NOT NULL,
              `lat` decimal(15,10) DEFAULT NULL,
              `lng` decimal(15,10) DEFAULT NULL,
              PRIMARY KEY (`id`),
              CONSTRAINT `FK_{$tAddress}_customer` FOREIGN KEY (`customer_id`) REFERENCES {$tCustomer} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
        ");
            /*
        ALTER TABLE {$tCustomer}
        ADD CONSTRAINT `FK_{$tCustomer}_billing` FOREIGN KEY (`default_billing_id`) REFERENCES {$tAddress} (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
        ADD CONSTRAINT `FK_{$tCustomer}_shipping` FOREIGN KEY (`default_shipping_id`) REFERENCES {$tAddress} (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
        */
    }

    public function upgrade__0_1_0__0_1_1()
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

    public function upgrade__0_1_1__0_1_2()
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

    public function upgrade__0_1_2__0_1_3()
    {
        BDb::ddlTableDef(FCom_Customer_Model_Address::table(), [
            'COLUMNS' => [
                'state' => 'RENAME region varchar(50)',
                'zip' => 'RENAME postcode varchar(20)',
            ],
        ]);
    }

    public function upgrade__0_1_3__0_1_4()
    {
        BDb::ddlTableDef(FCom_Customer_Model_Address::table(), [
            'COLUMNS' => [
                'middle_initial' => 'VARCHAR(2) NULL AFTER lastname',
                'prefix' => 'VARCHAR(10) NULL AFTER middle_initial',
                'suffix' => 'VARCHAR(10) NULL AFTER prefix',
                'company' => 'VARCHAR(50) NULL AFTER suffix',
            ],
        ]);
    }

    public function upgrade__0_1_4__0_1_5()
    {
        BDb::ddlTableDef(FCom_Customer_Model_Address::table(), [
            'COLUMNS' => [
                'email' => 'VARCHAR(100) NOT NULL AFTER customer_id',
            ],
        ]);
    }

    public function upgrade__0_1_5__0_1_6()
    {
        $table = FCom_Customer_Model_Customer::table();
        BDb::ddlTableDef($table, [
            'COLUMNS' => [
                  'create_dt'      => 'RENAME create_at datetime NOT NULL',
                  'update_dt'      => 'RENAME update_at datetime NOT NULL',
            ],
        ]);
        $table = FCom_Customer_Model_Address::table();
        BDb::ddlTableDef($table, [
            'COLUMNS' => [
                  'create_dt'      => 'RENAME create_at datetime NOT NULL',
                  'update_dt'      => 'RENAME update_at datetime NOT NULL',
            ],
        ]);
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $table = FCom_Customer_Model_Customer::table();
        BDb::ddlTableDef($table, [
            'COLUMNS' => [
                'status' => 'ENUM("review", "active", "disabled") NOT NULL DEFAULT "review"',
            ],
        ]);
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $table = FCom_Customer_Model_Customer::table();
        BDb::ddlTableDef($table, [
            'COLUMNS' => [
                'payment_method' => 'varchar(20) null',
                'payment_details' => 'text null',
            ],
        ]);
    }

    public function upgrade__0_1_8__0_1_9()
    {
        $table = FCom_Customer_Model_Customer::table();
        BDb::ddlTableDef($table, [
            'COLUMNS' => [
                'email' => 'varchar(255)',
            ],
        ]);
        $table = FCom_Customer_Model_Address::table();
        BDb::ddlTableDef($table, [
            'COLUMNS' => [
                'email' => 'varchar(255)',
            ],
        ]);
    }

    public function upgrade__0_1_9__0_1_10()
    {
        $table = FCom_Customer_Model_Customer::table();
        BDb::ddlTableDef($table, [
            'COLUMNS' => [
                'password_session_token' => 'varchar(16)',
            ],
        ]);
    }
}
