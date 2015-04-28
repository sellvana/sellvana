<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Customer_Migrate
 *
 * @property Sellvana_Customer_Model_Address $Sellvana_Customer_Model_Address
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */

class Sellvana_Customer_Migrate extends BClass
{
    public function install__0_1_14()
    {
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tAddress = $this->Sellvana_Customer_Model_Address->table();

        $this->BDb->ddlTableDef($tCustomer, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'email' => 'varchar(255) not null',
                'firstname' => 'varchar(50) not null',
                'lastname' => 'varchar(50) not null',
                'password_hash' => 'text',
                'default_shipping_id' => 'int unsigned default null',
                'default_billing_id' => 'int unsigned default null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
                'last_login' => 'datetime default null',
                'token' => 'varchar(20) default null',
                'token_at' => 'datetime default null',
                'payment_method' => 'varchar(20) default null',
                'payment_details' => 'text',
                'status' => "varchar(10) not null default 'new'",
                'password_session_token' => 'varchar(16)',
                'last_session_id' => 'varchar(40) null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_session_id' => '(last_session_id)',
            ],
        ]);

        $this->BDb->ddlTableDef($tAddress, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'customer_id' => 'int unsigned not null',
                'email' => 'varchar(255) not null',
                'firstname' => 'varchar(50) not null',
                'lastname' => 'varchar(50) not null',
                'middle_initial' => 'varchar(2) default null',
                'prefix' => 'varchar(10) default null',
                'suffix' => 'varchar(10) default null',
                'company' => 'varchar(50) default null',
                'attn' => 'varchar(50) default null',
                'street1' => 'text not null',
                'street2' => 'text',
                'street3' => 'text',
                'city' => 'varchar(50) not null',
                'region' => 'varchar(50) default null',
                'postcode' => 'varchar(20) default null',
                'country' => 'char(2) not null',
                'phone' => 'varchar(50) default null',
                'fax' => 'varchar(50) default null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
                'lat' => 'decimal(15,10) default null',
                'lng' => 'decimal(15,10) default null',
                'is_default_billing' => 'tinyint not null default 0',
                'is_default_shipping' => 'tinyint not null default 0',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'customer' => ['customer_id', $tCustomer],
            ]
        ]);
            /*
        ALTER TABLE {$tCustomer}
        ADD CONSTRAINT `FK_{$tCustomer}_billing` FOREIGN KEY (`default_billing_id`) REFERENCES {$tAddress} (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
        ADD CONSTRAINT `FK_{$tCustomer}_shipping` FOREIGN KEY (`default_shipping_id`) REFERENCES {$tAddress} (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
        */
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tAddress = $this->Sellvana_Customer_Model_Address->table();
        $this->BDb->ddlClearCache();
        if ($this->BDb->ddlFieldInfo($tAddress, "lat")) {
            return;
        }
        try {
            $this->BDb->run("
                ALTER TABLE {$tAddress}
                ADD COLUMN `lat` DECIMAL(15,10) NULL,
                ADD COLUMN `lng` DECIMAL(15,10) NULL;
            ");
        } catch (Exception $e) {}
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $this->BDb->ddlClearCache();
        if ($this->BDb->ddlFieldInfo($tCustomer, "payment_method")) {
            return;
        }
        try {
            $this->BDb->run("
                ALTER TABLE {$tCustomer}
                    ADD `payment_method` VARCHAR( 20 ) NOT NULL,
                    ADD `payment_details` TEXT NOT NULL
                    ;
            ");
        } catch (Exception $e) {}
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $this->BDb->ddlTableDef($this->Sellvana_Customer_Model_Address->table(), [
            BDb::COLUMNS => [
                'state' => 'RENAME region varchar(50)',
                'zip' => 'RENAME postcode varchar(20)',
            ],
        ]);
    }

    public function upgrade__0_1_3__0_1_4()
    {
        $this->BDb->ddlTableDef($this->Sellvana_Customer_Model_Address->table(), [
            BDb::COLUMNS => [
                'middle_initial' => 'VARCHAR(2) NULL AFTER lastname',
                'prefix' => 'VARCHAR(10) NULL AFTER middle_initial',
                'suffix' => 'VARCHAR(10) NULL AFTER prefix',
                'company' => 'VARCHAR(50) NULL AFTER suffix',
            ],
        ]);
    }

    public function upgrade__0_1_4__0_1_5()
    {
        $this->BDb->ddlTableDef($this->Sellvana_Customer_Model_Address->table(), [
            BDb::COLUMNS => [
                'email' => 'VARCHAR(100) NOT NULL AFTER customer_id',
            ],
        ]);
    }

    public function upgrade__0_1_5__0_1_6()
    {
        $table = $this->Sellvana_Customer_Model_Customer->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'create_dt'      => 'RENAME create_at datetime NOT NULL',
                  'update_dt'      => 'RENAME update_at datetime NOT NULL',
            ],
        ]);
        $table = $this->Sellvana_Customer_Model_Address->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'create_dt'      => 'RENAME create_at datetime NOT NULL',
                  'update_dt'      => 'RENAME update_at datetime NOT NULL',
            ],
        ]);
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $table = $this->Sellvana_Customer_Model_Customer->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'status' => 'ENUM("review", "active", "disabled") NOT NULL DEFAULT "review"',
            ],
        ]);
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $table = $this->Sellvana_Customer_Model_Customer->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'payment_method' => 'varchar(20) null',
                'payment_details' => 'text null',
            ],
        ]);
    }

    public function upgrade__0_1_8__0_1_9()
    {
        $table = $this->Sellvana_Customer_Model_Customer->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'email' => 'varchar(255)',
            ],
        ]);
        $table = $this->Sellvana_Customer_Model_Address->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'email' => 'varchar(255)',
            ],
        ]);
    }

    public function upgrade__0_1_9__0_1_10()
    {
        $table = $this->Sellvana_Customer_Model_Customer->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'password_session_token' => 'varchar(16)',
            ],
        ]);
    }

    public function upgrade__0_1_10__0_1_11()
    {
        $table = $this->Sellvana_Customer_Model_Customer->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'token_at' => 'datetime default null after token',
            ],
        ]);
    }

    public function upgrade__0_1_11__0_1_12()
    {
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tAddress = $this->Sellvana_Customer_Model_Address->table();
        $this->BDb->ddlTableDef($tAddress, [
            BDb::COLUMNS => [
                'is_default_billing' => 'tinyint not null default 0',
                'is_default_shipping' => 'tinyint not null default 0',
            ],
        ]);
        $this->BDb->run("UPDATE {$tAddress} a, {$tCustomer} c
            SET a.is_default_billing=IF(c.default_billing_id=a.id,1,0),
                a.is_default_shipping=IF(c.default_shipping_id=a.id,1,0)
            WHERE a.customer_id=c.id
        ");
    }

    public function upgrade__0_1_12__0_1_13()
    {
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $this->BDb->ddlTableDef($tCustomer, [
            BDb::COLUMNS => [
                'last_session_id' => 'varchar(40) null',
            ],
            BDb::KEYS => [
                'IDX_session_id' => '(last_session_id)',
            ],
        ]);
    }

    public function upgrade__0_1_13__0_1_14()
    {
        $table = $this->Sellvana_Customer_Model_Customer->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'status' => "varchar(10) not null default 'new'",
            ],
        ]);
    }
}
