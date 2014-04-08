<?php

class FCom_Admin_Migrate extends BClass
{
    public function install__0_1_7()
    {
        $tRole = FCom_Admin_Model_Role::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tRole} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `role_name` VARCHAR(50) NOT NULL,
            `permissions_data` TEXT NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tUser = FCom_Admin_Model_User::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tUser} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `superior_id` int(10) unsigned DEFAULT NULL,
            `username` varchar(255) NOT NULL,
            `is_superadmin` tinyint(4) NOT NULL DEFAULT '0',
            `role_id` int(11) unsigned DEFAULT NULL,
            `email` varchar(255) NOT NULL,
            `password_hash` varchar(255) NOT NULL,
            `firstname` varchar(100) DEFAULT NULL,
            `lastname` varchar(100) DEFAULT NULL,
            `phone` varchar(50) DEFAULT NULL,
            `phone_ext` varchar(50) DEFAULT NULL,
            `fax` varchar(50) DEFAULT NULL,
            `status` char(1) NOT NULL DEFAULT 'A',
            `tz` varchar(50) NOT NULL DEFAULT 'America/Los_Angeles',
            `locale` varchar(50) NOT NULL DEFAULT 'en_US',
            `create_at` datetime NOT NULL,
            `update_at` datetime DEFAULT NULL,
            `token` varchar(20) DEFAULT NULL,
            `token_at` datetime DEFAULT NULL,
            `api_username`  varchar(100) DEFAULT '' NOT NULL,
            `api_password`  varchar(100) DEFAULT '' NOT NULL,
            `api_password_hash` varchar(255)  NULL,
            `data_serialized` text  NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `UNQ_email` (`email`),
            UNIQUE KEY `UNQ_username` (`username`),
            CONSTRAINT `FK_{$tUser}_role` FOREIGN KEY (`role_id`) REFERENCES {$tRole} (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT `FK_{$tUser}_superior` FOREIGN KEY (`superior_id`) REFERENCES {$tUser} (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tPersonalize = FCom_Admin_Model_Personalize::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tPersonalize} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int(10) unsigned NOT NULL,
            `data_json` text,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tPersonalize}_user` FOREIGN KEY (`user_id`) REFERENCES {$tUser} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $tActivity = FCom_Admin_Model_Activity::table();
        $tActivityUser = FCom_Admin_Model_ActivityUser::table();

        BDb::ddlTableDef($tUser, array(
            'COLUMNS' => array(
                'data_serialized' => 'text',
            ),
        ));

        BDb::ddlTableDef($tActivity, array(
            'COLUMNS' => array(
                'id' => "int unsigned not null auto_increment",
                'status' => "enum('new', 'recent', 'archived') not null default 'new'",
                'type' => "enum('workflow', 'alert') not null default 'workflow'",
                'event_code' => "varchar(50) not null",
                'permissions' => "varchar(50)",
                'action_user_id' => 'int unsigned',
                'customer_id' => 'int unsigned',
                'order_id' => 'int unsigned',
                'create_at' => 'datetime not null',
                'data_serialized' => 'text',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_status_type_create' => '(`status`, `type`, `create_at`)',
            ),
        ));

        BDb::ddlTableDef($tActivityUser, array(
            'COLUMNS' => array(
                'id' => "int unsigned not null auto_increment",
                'activity_id' => "int unsigned not null",
                'user_id' => "int unsigned not null",
                'alert_user_status' => "enum('new', 'read', 'dismissed') not null default 'new'",
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_activity_user_status' => 'UNIQUE (`activity_id`, `user_id`, `alert_user_status`)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tActivityUser}_activity" => "FOREIGN KEY (activity_id) REFERENCES {$tActivity} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tActivityUser}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ));

        $tAggregate = FCom_Admin_Model_Aggregate::table();
        BDb::ddlTableDef($tAggregate, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'data_type' => 'varchar(20) not null',
                'data_args' => 'varchar(50) not null',
                'data_day' => 'date not null',
                //'range_type' => "enum('day') default 'day' not null",
                //'range_start' => 'date not null',
                'amount' => 'decimal(12,2) not null',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_data_type_args_day' => '(data_type, data_args, data_day)',
            ),
        ));
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tUser = FCom_Admin_Model_User::table();

        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tUser, 'is_superadmin')) {
            return;
        }

        try {
            BDb::run("
                ALTER TABLE {$tUser}
                ADD COLUMN `is_superadmin` TINYINT DEFAULT 0 NOT NULL AFTER `username`
                , ADD COLUMN `role_id` INT NULL AFTER `is_superadmin`
                , ADD COLUMN `token` varchar(20) DEFAULT NULL
                ;
            ");
        } catch (Exception $e) { }

        FCom_Admin_Model_Role::i()->install();
        BDb::run("
            UPDATE {$tUser} SET is_superadmin=1;
        ");
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tUser = FCom_Admin_Model_User::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tUser, 'token_dt')) {
            return;
        }
        try {
            BDb::run("
                ALTER TABLE {$tUser} ADD COLUMN `token_dt` DATETIME NULL AFTER `token`;
            ");
        } catch (Exception $e) { }
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tUser = FCom_Admin_Model_User::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tUser, 'api_username')) {
            return;
        }
        try {
            BDb::run("
                ALTER TABLE {$tUser}
                ADD COLUMN `api_username` varchar(100) DEFAULT '' NOT NULL,
                ADD COLUMN `api_password` varchar(40) DEFAULT '' NOT NULL
                ;
            ");
        } catch (Exception $e) { }
    }

    public function upgrade__0_1_3__0_1_4()
    {
        BDb::ddlTableDef(FCom_Admin_Model_User::table(), array(
            'COLUMNS' => array(
                'api_password' => 'DROP',
                'api_password_hash' => 'varchar(255) null',
            ),
        ));
    }

    public function upgrade__0_1_4__0_1_5()
    {
        $table = FCom_Admin_Model_User::table();
        BDb::ddlTableDef($table, array(
            'COLUMNS' => array(
                  'create_dt'  => 'RENAME create_at datetime NOT NULL',
                  'update_dt'  => 'RENAME update_at datetime DEFAULT NULL',
                  'token_dt'   => 'RENAME token_at datetime DEFAULT NULL',
            ),
        ));
    }

    public function upgrade__0_1_5__0_1_6()
    {
        $tActivity = FCom_Admin_Model_Activity::table();
        $tActivityUser = FCom_Admin_Model_ActivityUser::table();
        $tUser = FCom_Admin_Model_User::table();

        BDb::ddlTableDef($tUser, array(
            'COLUMNS' => array(
                'data_serialized' => 'text',
            ),
        ));

        BDb::ddlTableDef($tActivity, array(
            'COLUMNS' => array(
                'id' => "int unsigned not null auto_increment",
                'status' => "enum('new', 'recent', 'archived') not null default 'new'",
                'type' => "enum('workflow', 'alert') not null default 'workflow'",
                'event_code' => "varchar(50) not null",
                'permissions' => "varchar(50)",
                'action_user_id' => 'int unsigned',
                'customer_id' => 'int unsigned',
                'order_id' => 'int unsigned',
                'create_at' => 'datetime not null',
                'data_serialized' => 'text',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_status_type_create' => '(`status`, `type`, `create_at`)',
            ),
        ));

        BDb::ddlTableDef($tActivityUser, array(
            'COLUMNS' => array(
                'id' => "int unsigned not null auto_increment",
                'activity_id' => "int unsigned not null",
                'user_id' => "int unsigned not null",
                'alert_user_status' => "enum('new', 'read', 'dismissed') not null default 'new'",
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_activity_user_status' => 'UNIQUE (`activity_id`, `user_id`, `alert_user_status`)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tActivityUser}_activity" => "FOREIGN KEY (activity_id) REFERENCES {$tActivity} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tActivityUser}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ));
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $tAggregate = FCom_Admin_Model_Aggregate::table();
        BDb::ddlTableDef($tAggregate, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'data_type' => 'varchar(20) not null',
                'data_args' => 'varchar(50) not null',
                'data_day' => 'date not null',
                //'range_type' => "enum('day') default 'day' not null",
                //'range_start' => 'date not null',
                'amount' => 'decimal(12,2) not null',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_data_type_args_day' => '(data_type, data_args, data_day)',
            ),
        ));
    }
}
