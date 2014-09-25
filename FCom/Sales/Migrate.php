<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Migrate extends BClass
{
    public function install__0_2_12()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($tOrder, [
            'COLUMNS' => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'customer_id' => "int(10) unsigned DEFAULT NULL",
                'customer_email' => "varchar(100) DEFAULT NULL",
                'cart_id' => "int(10) unsigned NOT NULL",
                'status' => "varchar(50) NOT NULL",
                'item_qty' => "int(10) unsigned NOT NULL",
                'subtotal' => "decimal(12,2) NOT NULL DEFAULT '0.00'",
                'shipping_method' => "varchar(50) DEFAULT NULL",
                'shipping_service' => "varchar(50) DEFAULT NULL",
                'payment_method' => "varchar(50) DEFAULT NULL",
                'coupon_code' => "varchar(50) DEFAULT NULL",
                'tax' => "decimal(10,2) DEFAULT NULL",
                'balance' => "decimal(10,2) NOT NULL",
                'create_at' => "datetime DEFAULT NULL",
                'update_at' => "datetime DEFAULT NULL",
                'grandtotal' => "decimal(12,2) NOT NULL",
                'shipping_service_title' => "varchar(100) DEFAULT NULL",
                'data_serialized' => "text",
                'unique_id' => "varchar(15) NOT NULL",
                'admin_id' => "int(10) unsigned DEFAULT NULL",
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_cart_id' => 'UNIQUE (cart_id)',
            ],
        ]);

        $tItem = $this->FCom_Sales_Model_Order_Item->table();
        $this->BDb->ddlTableDef($tItem, [
            'COLUMNS' => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'order_id' => "int(10) unsigned DEFAULT NULL",
                'product_id' => "int(10) unsigned DEFAULT NULL",
                'qty' => "int(10) unsigned DEFAULT NULL",
                'total' => "decimal(12,2) NOT NULL DEFAULT '0.0000'",
                'product_info' => "text",
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tItem}_cart" => "FOREIGN KEY (`order_id`) REFERENCES {$tOrder} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $tAddress = $this->FCom_Sales_Model_Order_Address->table();
        $this->BDb->ddlTableDef($tAddress, [
            'COLUMNS' => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'order_id' => "int(11) unsigned NOT NULL",
                'atype' => "ENUM( 'shipping', 'billing' ) NOT NULL DEFAULT 'shipping'",
                'firstname' => "varchar(50)  DEFAULT NULL",
                'lastname' => "varchar(50)  DEFAULT NULL",
                'middle_initial' => "varchar(2)  DEFAULT NULL",
                'prefix' => "varchar(10)  DEFAULT NULL",
                'suffix' => "varchar(10)  DEFAULT NULL",
                'company' => "varchar(50)  DEFAULT NULL",
                'attn' => "varchar(50)  DEFAULT NULL",
                'street1' => "text  NOT NULL",
                'street2' => "text ",
                'street3' => "text ",
                'city' => "varchar(50)  NOT NULL",
                'region' => "varchar(50)  DEFAULT NULL",
                'postcode' => "varchar(20)  DEFAULT NULL",
                'country' => "char(2)  NOT NULL",
                'phone' => "varchar(50)  DEFAULT NULL",
                'fax' => "varchar(50)  DEFAULT NULL",
                'create_at' => "datetime NOT NULL",
                'update_at' => "datetime NOT NULL",
                'lat' => "decimal(15,10) DEFAULT NULL",
                'lng' => "decimal(15,10) DEFAULT NULL",
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tAddress}_cart" => "FOREIGN KEY (`order_id`) REFERENCES {$tOrder} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $tStatus = $this->FCom_Sales_Model_Order_CustomStatus->table();
        $this->BDb->ddlTableDef($tStatus, [
            'COLUMNS' => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'name' => "varchar(50) NOT NULL DEFAULT ''",
                'code' => "varchar(50) NOT NULL DEFAULT ''",
            ],
            'PRIMARY' => '(id)',
        ]);
        $this->BDb->run("
            insert into {$tStatus} (id,name,code) values(1, 'New', 'new'),(2,'Pending','pending'),(3,'Paid','paid')
        ");

        $tCart = $this->FCom_Sales_Model_Cart->table();
        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
        $tAddress = $this->FCom_Sales_Model_Cart_Address->table();

        $this->BDb->ddlTableDef($tCart, [
            'COLUMNS' => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'item_qty' => "decimal(12,2) NOT NULL DEFAULT '0.0000'",
                'item_num' => "smallint(6) unsigned NOT NULL DEFAULT '0'",
                'subtotal' => "decimal(12,2) NOT NULL DEFAULT '0.0000'",
                'tax_amount' => "decimal(12,2) NOT NULL default 0",
                'discount_amount' => "decimal(12,2) NOT NULL default 0",
                'grand_total' => "decimal(12,2) NOT NULL default 0",
                'cookie_token' => 'varchar(40) default null',
                'customer_id' => "int unsigned default NULL",
                'customer_email' => "varchar(100) NULL",
                'shipping_method' => "VARCHAR( 50 )  NULL ",
                'shipping_price' => "DECIMAL( 10, 2 ) NULL ",
                'shipping_service' => "CHAR( 2 )  NULL",
                'payment_method' => "VARCHAR( 50 ) NULL ",
                'payment_details' => "TEXT CHARACTER SET utf8   NULL",
                'coupon_code' => "VARCHAR( 50 ) default NULL",
                'status' => "ENUM( 'new', 'finished' ) NOT NULL DEFAULT 'new'",
                'create_at' => "DATETIME NULL",
                'update_at' => "DATETIME NULL",
                'data_serialized' => "text NULL",
                'last_calc_at' => "int unsigned",
                'admin_id' => "int(10) unsigned  NULL",
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_cookie_token' => 'UNIQUE (cookie_token)',
                'customer_id' => "(`customer_id`)",
                'status' => "(`status`)",
            ],
        ]);

        $this->BDb->ddlTableDef($tCartItem, [
            'COLUMNS' => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'cart_id' => "int(10) unsigned DEFAULT NULL",
                'product_id' => "int(10) unsigned DEFAULT NULL",
                'local_sku' => "varchar(100) DEFAULT NULL",
                'product_name' => "varchar(255) DEFAULT NULL",
                'qty' => "decimal(12,2) DEFAULT NULL",
                'price' => "decimal(12,2) NOT NULL DEFAULT '0.0000'",
                'rowtotal' => "decimal(12,2) NULL",
                'tax' => "decimal(12,2) NOT NULL default 0",
                'discount' => "decimal(12,2) NOT NULL default 0",

                'promo_id_buy' => "VARCHAR(50) default NULL",
                'promo_id_get' => "INT(10) UNSIGNED default NULL",
                'promo_qty_used' => "decimal(12,2) DEFAULT NULL",
                'promo_amt_used' => "decimal(12,2) DEFAULT NULL",

                'create_at' => "DATETIME NOT NULL",
                'update_at' => "DATETIME NOT NULL",
                'data_serialized' => "text  NULL",
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'cart_id' => "UNIQUE (`cart_id`,`product_id`)",
            ],
            'CONSTRAINTS' => [
                "FK_{$tCartItem}_cart" => "FOREIGN KEY (`cart_id`) REFERENCES {$tCart} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tAddress, [
            'COLUMNS' => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'cart_id' => "int(11) unsigned NOT NULL",
                'atype' => "ENUM( 'shipping', 'billing' ) NOT NULL DEFAULT 'shipping'",
                'firstname' => "varchar(50)  DEFAULT NULL",
                'lastname' => "varchar(50)  DEFAULT NULL",
                'middle_initial' => "varchar(2)  DEFAULT NULL",
                'prefix' => "varchar(10)  DEFAULT NULL",
                'suffix' => "varchar(10)  DEFAULT NULL",
                'company' => "varchar(50)  DEFAULT NULL",
                'attn' => "varchar(50)  DEFAULT NULL",
                'street1' => "text  NOT NULL",
                'street2' => "text ",
                'street3' => "text ",
                'city' => "varchar(50)  NOT NULL",
                'region' => "varchar(50)  DEFAULT NULL",
                'postcode' => "varchar(20)  DEFAULT NULL",
                'country' => "char(2)  NOT NULL",
                'phone' => "varchar(50)  DEFAULT NULL",
                'fax' => "varchar(50)  DEFAULT NULL",
                'email' => "VARCHAR( 100 ) NOT NULL",
                'create_at' => "datetime NOT NULL",
                'update_at' => "datetime NOT NULL",
                'lat' => "decimal(15,10) DEFAULT NULL",
                'lng' => "decimal(15,10) DEFAULT NULL",
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tAddress}_cart" => "FOREIGN KEY (`cart_id`) REFERENCES {$tCart} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order_Payment->table(), [
            'COLUMNS' => [
                'id'               => 'int (10) unsigned not null auto_increment',
                'create_at'        => 'datetime not null',
                'update_at'        => 'datetime null',
                'method'           => 'varchar(50) not null',
                'parent_id'        => 'int(10) null',
                'order_id'         => 'int(10) unsigned not null',
                'amount'           => 'decimal(12,2)',
                'data_serialized'  => 'text',
                'status'           => 'varchar(50)',
                'transaction_id'   => 'varchar(50)',
                'transaction_type' => 'varchar(50)',
                'online'           => 'BOOL',
            ],
            'PRIMARY' => '(id)',
            'KEYS'  => [
                'method'           => '(method)',
                'order_id'         => '(order_id)',
                'status'           => '(status)',
                'transaction_id'   => '(transaction_id)',
                'transaction_type' => '(transaction_type)',
            ],
            'CONSTRAINTS' => [
                'fk_payment_order' => "FOREIGN KEY (order_id) REFERENCES {$tOrder}(id) ON DELETE RESTRICT ON UPDATE CASCADE",
            ],
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($tOrder, [
            'COLUMNS' => [
                'created_dt' => 'datetime NULL',
                'purchased_dt' => 'datetime NULL',
            ]
        ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($tOrder, [
            'COLUMNS' => [
                'gt_base' => 'decimal(10,2) NOT NULL',
            ],
        ]);
    }

    public function upgrade__0_1_3__0_1_4()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($tOrder, ['COLUMNS' => [
            'status' => "enum('new', 'paid', 'pending') not null default 'new'",
        ]]);
    }

    public function upgrade__0_1_4__0_1_5()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($tOrder, ['COLUMNS' => [
            'shipping_service_title' => "varchar(100) not null default ''"
        ]]);
    }

    public function upgrade__0_1_5__0_1_6()
    {
        $tStatus = $this->FCom_Sales_Model_Order_CustomStatus->table();
        $this->BDb->ddlTableDef($tStatus, [
            'COLUMNS' => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'name' => "varchar(50) NOT NULL DEFAULT '' ",
                'code' => "varchar(50) NOT NULL DEFAULT ''",
            ],
            'PRIMARY' => '(`id`)',
        ]);
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $tStatus = $this->FCom_Sales_Model_Order_CustomStatus->table();
        $this->BDb->run("
            insert into {$tStatus}(id,name,code) values(1, 'New', 'new'),(2,'Pending','pending'),(3,'Paid','paid')
        ");
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $this->BDb->run("
            ALTER TABLE {$tOrder} ADD `status_id` int(11) not null default 0
        ");
    }

    public function upgrade__0_1_8__0_1_9()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $this->BDb->run("
            UPDATE  {$tOrder} SET `status_id` = 1 where status = 'new';
            UPDATE  {$tOrder} SET `status_id` = 2 where status = 'pending';
            UPDATE  {$tOrder} SET `status_id` = 3 where status = 'paid';
        ");
    }


    public function upgrade__0_1_9__0_1_10()
    {
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order_Address->table(), [
            'COLUMNS' => [
                'state' => 'RENAME region varchar(50)',
                'zip' => 'RENAME postcode varchar(20)',
            ],
        ]);
    }

    public function upgrade__0_1_10__0_2_0()
    {

        $tCart = $this->FCom_Sales_Model_Cart->table();
        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
        $tAddress = $this->FCom_Sales_Model_Cart_Address->table();

        $this->BDb->ddlTableDef($tCart, [
            'COLUMNS' => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'company_id' => "int(10) unsigned DEFAULT NULL",
                'location_id' => "int(10) unsigned DEFAULT NULL",
                'user_id' => "int(10) unsigned NOT NULL",
                'description' => "varchar(255) DEFAULT NULL",
                'sort_order' => "int(11) DEFAULT NULL",
                'item_qty' => "decimal(12,2) NOT NULL DEFAULT '0.0000'",
                'item_num' => "smallint(6) unsigned NOT NULL DEFAULT '0'",
                'subtotal' => "decimal(12,2) NOT NULL DEFAULT '0.0000'",
                'session_id' => "varchar(100) DEFAULT NULL",
                'shipping_method' => "VARCHAR( 50 ) NOT NULL ",
                'shipping_price' => "DECIMAL( 10, 2 ) NOT NULL ",
                'shipping_service' => "CHAR( 2 ) NOT NULL",
                'payment_method' => "VARCHAR( 50 ) NOT NULL ",
                'payment_details' => "TEXT CHARACTER SET utf8  NOT NULL",
                'discount_code' => "VARCHAR( 50 ) NOT NULL",
                'calc_balance' => "DECIMAL( 10, 2 ) NOT NULL ",
                'totals_json' => "TEXT NOT NULL",
                'status' => "ENUM( 'new', 'finished' ) NOT NULL DEFAULT 'new'",
                'create_dt' => "DATETIME NULL",
                'update_dt' => "DATETIME NULL",
            ],
            'PRIMARY' => '(`id`)',
            'KEYS' => [
                'session_id' => "UNIQUE (`session_id`)",
                'UNQ_user_id' => "UNIQUE (`user_id`,`description`,`session_id`)",
                'company_id' => "(`company_id`)",
            ],
        ]);

        $this->BDb->ddlTableDef($tCartItem, [
            'COLUMNS' => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'cart_id' => "int(10) unsigned DEFAULT NULL",
                'product_id' => "int(10) unsigned DEFAULT NULL",
                'qty' => "decimal(12,2) DEFAULT NULL",
                'price' => "decimal(12,2) NOT NULL DEFAULT '0.0000'",
                'rowtotal' => "decimal(12,2) NULL",

                'promo_id_buy' => "VARCHAR(50) NOT NULL",
                'promo_id_get' => "INT(10) UNSIGNED NOT NULL",
                'promo_qty_used' => "decimal(12,2) DEFAULT NULL",
                'promo_amt_used' => "decimal(12,2) DEFAULT NULL",

                'create_dt' => "DATETIME NOT NULL",
                'update_dt' => "DATETIME NOT NULL",
            ],
            'PRIMARY' => '(`id`)',
            'KEYS' => [
                'UNQ_cart_id' => "UNIQUE (`cart_id`,`product_id`)",
            ],
            'CONSTRAINTS' => [
                "FK_{$tCartItem}_cart" => "FOREIGN KEY (`cart_id`) REFERENCES {$tCart} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tAddress, [
            'COLUMNS' => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'cart_id' => "int(11) unsigned NOT NULL",
                'atype' => "ENUM( 'shipping', 'billing' ) NOT NULL DEFAULT 'shipping'",
                'firstname' => "varchar(50)  DEFAULT NULL",
                'lastname' => "varchar(50)  DEFAULT NULL",
                'attn' => "varchar(50)  DEFAULT NULL",
                'street1' => "text  NOT NULL",
                'street2' => "text ",
                'street3' => "text ",
                'city' => "varchar(50)  NOT NULL",
                'state' => "varchar(50)  DEFAULT NULL",
                'zip' => "varchar(20)  DEFAULT NULL",
                'country' => "char(2)  NOT NULL",
                'phone' => "varchar(50)  DEFAULT NULL",
                'fax' => "varchar(50)  DEFAULT NULL",
                'email' => "VARCHAR( 100 ) NOT NULL",
                'create_dt' => "datetime NOT NULL",
                'update_dt' => "datetime NOT NULL",
                'lat' => "decimal(15,10) DEFAULT NULL",
                'lng' => "decimal(15,10) DEFAULT NULL",
            ],
            'PRIMARY' => '(`id`)',
            'CONSTRAINTS' => [
                "FK_{$tAddress}_cart" => "FOREIGN KEY (`cart_id`) REFERENCES {$tCart} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);
    }

    public function upgrade__0_2_0__0_2_1()
    {
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
            'KEYS' => [
                'NewIndex1' => 'DROP',
                'user_id' => 'DROP',
            ],
        ]);
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
            'COLUMNS' => [
                'data_serialized' => 'text',
                'company_id' => 'DROP',
                'location_id' => 'DROP',
                'description' => 'DROP',
                'user_id' => 'DROP',
                'totals_json' => 'DROP',
                'calc_balance' => 'DROP',
                'sort_order' => 'DROP',
                'discount_code' => 'RENAME coupon_code varchar(50) null',
                'customer_id' => 'int unsigned not null after session_id',
                'customer_email' => 'varchar(100) null after customer_id',
                'tax_amount' => 'decimal(12,2) not null default 0 after subtotal',
                'discount_amount' => 'decimal(12,2) not null default 0 after tax_amount',
                'grand_total' => 'decimal(12,2) not null default 0 after discount_amount',
                'status' => "varchar(10) not null default 'new'",
            ],
            'KEYS' => [
                'session_id' => '(session_id)',
                'customer_id' => '(customer_id)',
                'status' => '(status)',
            ],
        ]);

        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart_Item->table(), [
            'COLUMNS' => [
                'local_sku' => 'varchar(100) null after product_id',
                'product_name' => 'varchar(255) null after local_sku',
                'tax' => 'decimal(12,2) not null default 0 after rowtotal',
                'discount' => 'decimal(12,2) not null default 0 after tax',
                'data_serialized' => 'text after update_dt',
            ],
        ]);
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart_Address->table(), [
            'COLUMNS' => [
                'state' => 'RENAME region varchar(50)',
                'zip' => 'RENAME postcode varchar(20)',
            ],
        ]);
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order->table(), [
            'COLUMNS' => [
                'user_id' => 'RENAME customer_id int unsigned null',
                'discount_code' => 'RENAME coupon_code varchar(50) null',
                //'tax' => 'decimal(10,2) null'??
                'data_serialized' => 'text',
            ],
        ]);
    }

    public function upgrade__0_2_1__0_2_2()
    {
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
            'COLUMNS' => [
                'last_calc_at' => 'int unsigned',
            ],
        ]);
    }


    public function upgrade__0_2_2__0_2_3()
    {
        if (!$this->BDb->ddlTableExists('fcom_sales_order_address')) {
            $this->BDb->run("
                RENAME TABLE fcom_sales_address TO fcom_sales_order_address;
            ");
        }
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart_Address->table(), [
            'COLUMNS' => [
                'middle_initial' => 'VARCHAR(2) NULL AFTER lastname',
                'prefix' => 'VARCHAR(10) NULL AFTER middle_initial',
                'suffix' => 'VARCHAR(10) NULL AFTER prefix',
                'company' => 'VARCHAR(50) NULL AFTER suffix',
            ],
        ]);
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order->table(), [
            'COLUMNS' => [
                'customer_email' => 'VARCHAR(100) NULL AFTER customer_id',
            ],
        ]);
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order_Address->table(), [
            'COLUMNS' => [
                'middle_initial' => 'VARCHAR(2) NULL AFTER lastname',
                'prefix' => 'VARCHAR(10) NULL AFTER middle_initial',
                'suffix' => 'VARCHAR(10) NULL AFTER prefix',
                'company' => 'VARCHAR(50) NULL AFTER suffix',
            ],
        ]);
    }

    public function upgrade__0_2_3__0_2_4()
    {
        // todo update created at fields

        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order->table(), [
            'COLUMNS' => [
                'created_dt' => 'RENAME created_at datetime DEFAULT NULL',
                'purchased_dt' => 'RENAME updated_at datetime DEFAULT NULL',
                'gt_base' => 'RENAME grandtotal decimal(12,2) NOT NULL',
                'tax' => 'decimal(10,2) NULL',
                'unique_id' => 'varchar(15) NOT NULL',
                'status' => 'varchar(50) NOT NULL',
                'shippping_service' => 'DROP',
                'payment_details' => 'DROP',
                'status_id' => 'DROP',
                'totals_json' => 'DROP',
            ],
        ]);
    }

    public function upgrade__0_2_4__0_2_5()
    {
        foreach ([$this->FCom_Sales_Model_Cart_Item->table(),
           $this->FCom_Sales_Model_Cart_Address->table(),
           $this->FCom_Sales_Model_Order_Address->table(),
        ] as $table) {
            $this->BDb->ddlTableDef($table, [
                'COLUMNS' => [
                    'create_dt' => 'RENAME create_at datetime NOT NULL',
                    'update_dt' => 'RENAME update_at datetime NOT NULL',
                ],
            ]);
        }
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
            'COLUMNS' => [
                'create_dt' => 'RENAME create_at datetime NULL',
                'update_dt' => 'RENAME update_at datetime NULL',
            ],
        ]);
    }

    public function upgrade__0_2_5__0_2_6()
    {

        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order->table(), [
            'COLUMNS' => [
                'created_at' => 'RENAME create_at datetime DEFAULT NULL',
                'updated_at' => 'RENAME update_at datetime DEFAULT NULL',
            ],
        ]);
    }

    public function upgrade__0_2_6__0_2_7()
    {
        $oTable = $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order_Payment->table(), [
            'COLUMNS' => [
                'id'               => 'int (10) unsigned not null auto_increment',
                'create_at'        => 'datetime not null',
                'update_at'        => 'datetime null',
                'method'           => 'varchar(50) not null',
                'parent_id'        => 'int(10) null',
                'order_id'         => 'int(10) unsigned not null',
                'amount'           => 'decimal(12,2)',
                'data_serialized'  => 'text',
                'status'           => 'varchar(50)',
                'transaction_id'   => 'varchar(50)',
                'transaction_type' => 'varchar(50)',
                'online'           => 'BOOL',
            ],
            'PRIMARY' => '(id)',
            'KEYS'  => [
                'method'           => '(method)',
                'order_id'         => '(order_id)',
                'status'           => '(status)',
                'transaction_id'   => '(transaction_id)',
                'transaction_type' => '(transaction_type)',
            ],
            'CONSTRAINTS' => [
                'fk_payment_order' => "FOREIGN KEY (order_id) REFERENCES {$oTable}(id) ON DELETE RESTRICT ON UPDATE CASCADE",
            ],
        ]);
    }

    public function upgrade__0_2_7__0_2_8()
    {
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order->table(), [
                'COLUMNS' => [
                    'admin_id' => 'int(10) unsigned NOT NULL',
                ],
            ]);

        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
                'COLUMNS' => [
                    'admin_id' => 'int(10) unsigned NOT NULL',
                ],
            ]);
    }

    public function upgrade__0_2_8__0_2_9()
    {
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
            'COLUMNS' => [
                'customer_id' => 'int unsigned null',
                'shipping_method' => 'varchar(50) null',
                'shipping_price' => 'decimal(10,2) null',
                'shipping_service' => 'varchar(50) null',
                'payment_method' => 'varchar(50) null',
                'payment_details' => 'text null',
                'admin_id' => 'int unsigned null',
            ],
        ]);
    }

    public function upgrade__0_2_9__0_2_10()
    {
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order->table(), [
            'COLUMNS' => [
                'shipping_method' => 'varchar(50) null',
                'shipping_service' => 'varchar(50) null',
                'shipping_service_title' => 'varchar(100) null',
                'payment_method' => 'varchar(50) null',
                'admin_id' => 'int unsigned null',
            ],
        ]);
    }

    public function upgrade__0_2_10__0_2_11()
    {
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
            'COLUMNS' => [
                'coupon_code' => 'varchar(50) DEFAULT NULL',
                'promo_id_buy' => 'VARCHAR(50) DEFAULT NULL',
                'promo_id_get' => 'INT(10) UNSIGNED DEFAULT NULL',
                'promo_qty_used' => 'decimal(12,2) DEFAULT NULL',
                'promo_amt_used' => 'decimal(12,2) DEFAULT NULL',
            ],
        ]);
    }

    public function upgrade__0_2_11__0_2_12()
    {
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
            'COLUMNS' => [
                'session_id' => 'DROP',
                'cookie_token' => 'varchar(40) default null after grand_total',
                'status' => "varchar(10) default 'new'",
            ],
            'KEYS' => [
                'session_id' => 'DROP',
                'UNQ_cookie_token' => 'UNIQUE (cookie_token)',
                'IDX_status_cookie_token' => '(`status`, cookie_token)',
            ],
        ]);
    }

    public function upgrade__0_2_12__0_2_13()
    {
        $tCart = $this->FCom_Sales_Model_Cart->table();
        $this->BDb->ddlTableDef($tCart, [
            'COLUMNS' => [
                'same_address' => "tinyint(1) not null default 0",
            ],
        ]);

        $tOrder= $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($tOrder, [
            'COLUMNS' => [
                'same_address' => "tinyint(1) not null default 0",
            ],
        ]);
    }
}
