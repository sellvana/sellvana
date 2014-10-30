<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Admin_Migrate extends BClass
{
    public function install__0_1_8()
    {
        $tRole = $this->FCom_Admin_Model_Role->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tRole} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `role_name` VARCHAR(50) NOT NULL,
            `permissions_data` TEXT NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tUser = $this->FCom_Admin_Model_User->table();
        $this->BDb->run("
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
            `password_session_token` varchar(16),
            `data_serialized` text  NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `UNQ_email` (`email`),
            UNIQUE KEY `UNQ_username` (`username`),
            CONSTRAINT `FK_{$tUser}_role` FOREIGN KEY (`role_id`) REFERENCES {$tRole} (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT `FK_{$tUser}_superior` FOREIGN KEY (`superior_id`) REFERENCES {$tUser} (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tPersonalize = $this->FCom_Admin_Model_Personalize->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tPersonalize} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int(10) unsigned NOT NULL,
            `data_json` text,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tPersonalize}_user` FOREIGN KEY (`user_id`) REFERENCES {$tUser} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $tActivity = $this->FCom_Admin_Model_Activity->table();
        $tActivityUser = $this->FCom_Admin_Model_ActivityUser->table();

        $this->BDb->ddlTableDef($tUser, [
            'COLUMNS' => [
                'data_serialized' => 'text',
            ],
        ]);

        $this->BDb->ddlTableDef($tActivity, [
            'COLUMNS' => [
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
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_status_type_create' => '(`status`, `type`, `create_at`)',
            ],
        ]);

        $this->BDb->ddlTableDef($tActivityUser, [
            'COLUMNS' => [
                'id' => "int unsigned not null auto_increment",
                'activity_id' => "int unsigned not null",
                'user_id' => "int unsigned not null",
                'alert_user_status' => "enum('new', 'read', 'dismissed') not null default 'new'",
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_activity_user_status' => 'UNIQUE (`activity_id`, `user_id`, `alert_user_status`)',
            ],
            'CONSTRAINTS' => [
                'activity' => ['activity_id', $tActivity],
                'user' => ['user_id', $tUser],
            ],
        ]);

        $tAggregate = $this->FCom_Admin_Model_Aggregate->table();
        $this->BDb->ddlTableDef($tAggregate, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'data_type' => 'varchar(20) not null',
                'data_args' => 'varchar(50) not null',
                'data_day' => 'date not null',
                //'range_type' => "enum('day') default 'day' not null",
                //'range_start' => 'date not null',
                'amount' => 'decimal(12,2) not null',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_data_type_args_day' => '(data_type, data_args, data_day)',
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tUser = $this->FCom_Admin_Model_User->table();

        $this->BDb->ddlClearCache();
        if ($this->BDb->ddlFieldInfo($tUser, 'is_superadmin')) {
            return;
        }

        try {
            $this->BDb->run("
                ALTER TABLE {$tUser}
                ADD COLUMN `is_superadmin` TINYINT DEFAULT 0 NOT NULL AFTER `username`
                , ADD COLUMN `role_id` INT NULL AFTER `is_superadmin`
                , ADD COLUMN `token` varchar(20) DEFAULT NULL
                ;
            ");
        } catch (Exception $e) { }

        $this->FCom_Admin_Model_Role->install();
        $this->BDb->run("
            UPDATE {$tUser} SET is_superadmin=1;
        ");
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tUser = $this->FCom_Admin_Model_User->table();
        $this->BDb->ddlClearCache();
        if ($this->BDb->ddlFieldInfo($tUser, 'token_dt')) {
            return;
        }
        try {
            $this->BDb->run("
                ALTER TABLE {$tUser} ADD COLUMN `token_dt` DATETIME NULL AFTER `token`;
            ");
        } catch (Exception $e) { }
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tUser = $this->FCom_Admin_Model_User->table();
        $this->BDb->ddlClearCache();
        if ($this->BDb->ddlFieldInfo($tUser, 'api_username')) {
            return;
        }
        try {
            $this->BDb->run("
                ALTER TABLE {$tUser}
                ADD COLUMN `api_username` varchar(100) DEFAULT '' NOT NULL,
                ADD COLUMN `api_password` varchar(40) DEFAULT '' NOT NULL
                ;
            ");
        } catch (Exception $e) { }
    }

    public function upgrade__0_1_3__0_1_4()
    {
        $this->BDb->ddlTableDef($this->FCom_Admin_Model_User->table(), [
            'COLUMNS' => [
                'api_password' => 'DROP',
                'api_password_hash' => 'varchar(255) null',
            ],
        ]);
    }

    public function upgrade__0_1_4__0_1_5()
    {
        $table = $this->FCom_Admin_Model_User->table();
        $this->BDb->ddlTableDef($table, [
            'COLUMNS' => [
                  'create_dt'  => 'RENAME create_at datetime NOT NULL',
                  'update_dt'  => 'RENAME update_at datetime DEFAULT NULL',
                  'token_dt'   => 'RENAME token_at datetime DEFAULT NULL',
            ],
        ]);
    }

    public function upgrade__0_1_5__0_1_6()
    {
        $tActivity = $this->FCom_Admin_Model_Activity->table();
        $tActivityUser = $this->FCom_Admin_Model_ActivityUser->table();
        $tUser = $this->FCom_Admin_Model_User->table();

        $this->BDb->ddlTableDef($tUser, [
            'COLUMNS' => [
                'data_serialized' => 'text',
            ],
        ]);

        $this->BDb->ddlTableDef($tActivity, [
            'COLUMNS' => [
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
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_status_type_create' => '(`status`, `type`, `create_at`)',
            ],
        ]);

        $this->BDb->ddlTableDef($tActivityUser, [
            'COLUMNS' => [
                'id' => "int unsigned not null auto_increment",
                'activity_id' => "int unsigned not null",
                'user_id' => "int unsigned not null",
                'alert_user_status' => "enum('new', 'read', 'dismissed') not null default 'new'",
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_activity_user_status' => 'UNIQUE (`activity_id`, `user_id`, `alert_user_status`)',
            ],
            'CONSTRAINTS' => [
                'activity' => ['activity_id', $tActivity],
                'user' => ['user_id', $tUser],
            ],
        ]);
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $tAggregate = $this->FCom_Admin_Model_Aggregate->table();
        $this->BDb->ddlTableDef($tAggregate, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'data_type' => 'varchar(20) not null',
                'data_args' => 'varchar(50) not null',
                'data_day' => 'date not null',
                //'range_type' => "enum('day') default 'day' not null",
                //'range_start' => 'date not null',
                'amount' => 'decimal(12,2) not null',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_data_type_args_day' => '(data_type, data_args, data_day)',
            ],
        ]);
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $tUser = $this->FCom_Admin_Model_User->table();
        $this->BDb->ddlTableDef($tUser, [
            'COLUMNS' => [
                'password_session_token' => 'varchar(16)',
            ],
        ]);
    }

    public function upgrade__0_1_8__0_1_9()
    {
        $tMedia = $this->FCom_Core_Model_MediaLibrary->table();
        $this->BDb->ddlTableDef($tMedia, [
            'COLUMNS' => [
                'folder' => 'varchar(255) NOT NULL',
            ],
        ]);
    }
}
