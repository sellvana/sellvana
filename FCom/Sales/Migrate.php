<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Migrate extends BClass
{
    public function install__0_2_14()
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
                'same_address' => "tinyint(1) not null default 0",
                'state_overall' => "varchar(15) not null default 'new'",
                'state_delivery' => "varchar(15) not null default 'pending'",
                'state_payment' => "varchar(15) not null default 'new'",
                'state_custom' => "varchar(15) not null default ''",
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_cart_id' => 'UNIQUE (cart_id)',
            ],
        ]);

        $tOrderItem = $this->FCom_Sales_Model_Order_Item->table();
        $this->BDb->ddlTableDef($tOrderItem, [
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
                "FK_{$tOrderItem}_cart" => "FOREIGN KEY (`order_id`) REFERENCES {$tOrder} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $tOrderAddress = $this->FCom_Sales_Model_Order_Address->table();
        $this->BDb->ddlTableDef($tOrderAddress, [
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
                "FK_{$tOrderAddress}_cart" => "FOREIGN KEY (`order_id`) REFERENCES {$tOrder} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
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
        $tCartAddress = $this->FCom_Sales_Model_Cart_Address->table();

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
                'shipping_method' => "VARCHAR(50)  NULL ",
                'shipping_price' => "DECIMAL(10, 2) NULL ",
                'shipping_service' => "CHAR(2)  NULL",
                'payment_method' => "VARCHAR(50) NULL ",
                'payment_details' => "TEXT CHARACTER SET utf8   NULL",
                'coupon_code' => "VARCHAR(50) default NULL",
                'status' => "ENUM('new', 'finished') NOT NULL DEFAULT 'new'",
                'create_at' => "DATETIME NULL",
                'update_at' => "DATETIME NULL",
                'data_serialized' => "text NULL",
                'last_calc_at' => "int unsigned",
                'admin_id' => "int(10) unsigned  NULL",
                'same_address' => "tinyint(1) not null default 0",
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

        $this->BDb->ddlTableDef($tCartAddress, [
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
                "FK_{$tCartAddress}_cart" => "FOREIGN KEY (`cart_id`) REFERENCES {$tCart} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);
        $tOrderPayment = $this->FCom_Sales_Model_Order_Payment->table();
        $this->BDb->ddlTableDef($tOrderPayment, [
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
                "FK_{$tOrderPayment}_order" => "FOREIGN KEY (order_id) REFERENCES {$tOrder} (id) ON DELETE RESTRICT ON UPDATE CASCADE",
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
        $tCartAddress = $this->FCom_Sales_Model_Cart_Address->table();

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

        $this->BDb->ddlTableDef($tCartAddress, [
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
                "FK_{$tCartAddress}_cart" => "FOREIGN KEY (`cart_id`) REFERENCES {$tCart} (`id`) ON DELETE CASCADE ON UPDATE CASCADE",
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
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $tOrderPayment = $this->FCom_Sales_Model_Order_Payment->table();
        $this->BDb->ddlTableDef($tOrderPayment, [
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
                "FK_{$tOrderPayment}_order" => "FOREIGN KEY (order_id) REFERENCES {$tOrder} (id) ON DELETE RESTRICT ON UPDATE CASCADE",
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

    public function upgrade__0_2_13__0_3_0()
    {
        $tUser = $this->FCom_Admin_Model_User->table();
        $tCart = $this->FCom_Sales_Model_Cart->table();
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $tOrderItem = $this->FCom_Sales_Model_Order_Item->table();
        $tOrderShipment = $this->FCom_Sales_Model_Order_Shipment->table();
        $tOrderShipmentItem = $this->FCom_Sales_Model_Order_Shipment_Item->table();
        $tOrderPayment = $this->FCom_Sales_Model_Order_Payment->table();
        $tOrderPaymentItem = $this->FCom_Sales_Model_Order_Payment_Item->table();
        $tOrderReturn = $this->FCom_Sales_Model_Order_Return->table();
        $tOrderReturnItem = $this->FCom_Sales_Model_Order_Return_Item->table();
        $tOrderRefund = $this->FCom_Sales_Model_Order_Refund->table();
        $tOrderRefundItem = $this->FCom_Sales_Model_Order_Refund_Item->table();
        $tOrderHistory = $this->FCom_Sales_Model_Order_History->table();
        $tOrderComment = $this->FCom_Sales_Model_Order_Comment->table();
        $tStateCustom = $this->FCom_Sales_Model_StateCustom->table();

        $this->BDb->ddlTableDef($tCart, [
            'COLUMNS' => [
                'status' => "RENAME state_overall varchar(10) not null default ''",

                'billing_company' => 'varchar(50)',
                'billing_attn' => 'varchar(50)',
                'billing_firstname' => 'varchar(50)',
                'billing_lastname' => 'varchar(50)',
                'billing_street' => 'varchar(255)',
                'billing_city' => 'varchar(50)',
                'billing_region' => 'varchar(50)',
                'billing_postcode' => 'varchar(20)',
                'billing_country' => 'char(2)',
                'billing_phone' => 'varchar(50)',
                'billing_fax' => 'varchar(50)',

                'shipping_company' => 'varchar(50)',
                'shipping_attn' => 'varchar(50)',
                'shipping_firstname' => 'varchar(50)',
                'shipping_lastname' => 'varchar(50)',
                'shipping_street' => 'varchar(255)',
                'shipping_city' => 'varchar(50)',
                'shipping_region' => 'varchar(50)',
                'shipping_postcode' => 'varchar(20)',
                'shipping_country' => 'char(2)',
                'shipping_phone' => 'varchar(50)',
                'shipping_fax' => 'varchar(50)',
            ],
        ]);

        $this->BDb->ddlTableDef($tOrder, [
            'COLUMNS' => [
                'billing_company' => 'varchar(50)',
                'billing_attn' => 'varchar(50)',
                'billing_firstname' => 'varchar(50)',
                'billing_lastname' => 'varchar(50)',
                'billing_street' => 'varchar(255)',
                'billing_city' => 'varchar(50)',
                'billing_region' => 'varchar(50)',
                'billing_postcode' => 'varchar(20)',
                'billing_country' => 'char(2)',
                'billing_phone' => 'varchar(50)',
                'billing_fax' => 'varchar(50)',

                'shipping_company' => 'varchar(50)',
                'shipping_attn' => 'varchar(50)',
                'shipping_firstname' => 'varchar(50)',
                'shipping_lastname' => 'varchar(50)',
                'shipping_street' => 'varchar(255)',
                'shipping_city' => 'varchar(50)',
                'shipping_region' => 'varchar(50)',
                'shipping_postcode' => 'varchar(20)',
                'shipping_country' => 'char(2)',
                'shipping_phone' => 'varchar(50)',
                'shipping_fax' => 'varchar(50)',

                'amount_paid' => 'decimal(12,2)',
                'amount_due' => 'decimal(12,2)',
                'amount_refunded' => 'decimal(12,2)',

                'state_overall' => "varchar(10) not null default 'new'",
                'state_delivery' => "varchar(10) not null default 'pending'",
                'state_payment' => "varchar(10) not null default 'new'",
                'state_custom' => "varchar(10) not null default ''",
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderItem, [
            'COLUMNS' => [
                'data_serialized' => 'text null',

                'qty_ordered' => 'int not null',
                'qty_backordered' => 'int not null default 0',
                'qty_canceled' => 'int not null default 0',
                'qty_shipped' => 'int not null default 0',
                'qty_returned' => 'int not null default 0',

                'state_overall' => "varchar(10) not null default 'new'",
                'state_delivery' => "varchar(10) not null default 'pending'",
                'state_payment' => "varchar(10) not null default 'new'",
                'state_custom' => "varchar(10) not null default ''",
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderShipment, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'state_overall' => "varchar(10) not null default 'new'",
                'state_custom' => "varchar(10) not null default ''",
                'carrier_code' => 'varchar(20)',
                'service_code' => 'varchar(20)',
                'carrier_desc' => 'varchar(50)',
                'service_desc' => 'varchar(50)',
                'carrier_price' => 'decimal(12,2)',
                'customer_price' => 'decimal(12,2)',
                'shipping_size' => 'varchar(30)',
                'shipping_weight' => 'decimal(12,2)',
                'num_items' => 'smallint',
                'create_at' => 'datetime not null',
                'packed_at' => 'datetime',
                'estimated_ship_at' => 'datetime',
                'shipped_at' => 'datetime',
                'estimated_delivery_at' => 'datetime',
                'delivered_at' => 'datetime',
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tOrderShipment}_order" => "FOREIGN KEY (order_id) REFERENCES {$tOrder} (id) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderShipmentItem, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tOrderShipmentItem}_order" => "FOREIGN KEY (order_id) REFERENCES {$tOrder} (id) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tOrderShipmentItem}_order_item" => "FOREIGN KEY (order_item_id) REFERENCES {$tOrderItem} (id) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderPayment, [
            'COLUMNS' => [
                'order_id'         => 'int unsigned not null',
                'method'           => 'RENAME payment_method varchar(50) not null',
                'amount'           => 'RENAME amount_authorized decimal(12,2)',
                'amount_due'       => 'decimal(12,2)',
                'amount_captured'  => 'decimal(12,2)',
                'amount_refunded'  => 'decimal(12,2)',
                'status'           => 'RENAME transaction_status varchar(50)',
                'state_overall'    => "varchar(20) not null default 'new'",
                'state_custom'     => "varchar(20) not null default ''",
                'transaction_fee'  => 'decimal(12,2)',
                'data_serialized'  => 'text',
            ],
            'KEYS'  => [
                'method'           => 'DROP',
                'order_id'         => 'DROP',
                'status'           => 'DROP',
                'transaction_id'   => 'DROP',
                'transaction_type' => 'DROP',

                'IDX_method_status' => '(payment_method, transaction_status)',
                'IDX_order'         => '(order_id)',
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
                'IDX_transaction_id' => '(transaction_id)',
                'IDX_transaction_type' => '(transaction_type)',
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderPaymentItem, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'order_item_id' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tOrderPaymentItem}_order" => "FOREIGN KEY (order_id) REFERENCES {$tOrder} (id) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tOrderPaymentItem}_order_item" => "FOREIGN KEY (order_item_id) REFERENCES {$tOrderItem} (id) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderReturn, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'state_overall' => "varchar(10) not null default 'new'",
                'state_custom' => "varchar(10) not null default ''",
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tOrderReturn}_order" => "FOREIGN KEY (order_id) REFERENCES {$tOrder} (id) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderReturnItem, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tOrderReturnItem}_order" => "FOREIGN KEY (order_id) REFERENCES {$tOrder} (id) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tOrderReturnItem}_order_item" => "FOREIGN KEY (order_item_id) REFERENCES {$tOrderItem} (id) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderRefund, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'state_overall' => "varchar(10) not null default 'new'",
                'state_custom' => "varchar(10) not null default ''",
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tOrderRefund}_order" => "FOREIGN KEY (order_id) REFERENCES {$tOrder} (id) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderRefundItem, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tOrderRefundItem}_order" => "FOREIGN KEY (order_id) REFERENCES {$tOrder} (id) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tOrderRefundItem}_order_item" => "FOREIGN KEY (order_item_id) REFERENCES {$tOrderItem} (id) ON DELETE CASCADE ON UPDATE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderHistory, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                // order, item, shipment, payment, refund, return
                'entity_type' => "varchar(20) not null default 'order'",
                'entity_id' => 'int unsigned default null',
                'order_item_id' => 'int unsigned default null',
                'event_type' => 'varchar(50) not null',
                'event_description' => 'text',
                'event_at' => 'datetime',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
                'user_id' => 'int unsigned default null',
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_event_at' => '(event_at)',
                'IDX_order_id' => '(order_id, event_at)',
                'IDX_entity_type_id' => '(entity_type, entity_id, event_at)',
                'IDX_event_type_at' => '(event_type, event_at)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tOrderHistory}_order" => "FOREIGN KEY (order_id) REFERENCES {$tOrder} (id) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tOrderHistory}_order_item" => "FOREIGN KEY (order_item_id) REFERENCES {$tOrderItem} (id) ON DELETE SET NULL ON UPDATE CASCADE",
                "FK_{$tOrderHistory}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON DELETE SET NULL ON UPDATE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderComment, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'comment_text' => 'text',
                'from_admin' => 'tinyint not null',
                'is_internal' => 'tinyint not null',
                'user_id' => 'int unsigned default null',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_order_create' => '(order_id, create_at)',
                'IDX_admin_user' => '(from_admin, user_id)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tOrderComment}_order" => "FOREIGN KEY (order_id) REFERENCES {$tOrder} (id) ON DELETE CASCADE ON UPDATE CASCADE",
                "FK_{$tOrderComment}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON DELETE SET NULL ON UPDATE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tStateCustom, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'entity_type' => 'varchar(15) not null',
                'state_code' => 'varchar(20) not null',
                'state_label' => 'varchar(50) not null',
                'concrete_class' => 'varchar(100) null',
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
        ]);

        $tCartAddress = $this->FCom_Sales_Migrate_Model_Cart_Address->table();
        if ($this->BDb->ddlTableExists($tCartAddress)) {
            $addresses = $this->FCom_Sales_Migrate_Model_Cart_Address->orm()->find_many();
            foreach ($addresses as $a) {
                $prefix = $a->get('atype') . '_';
                $this->FCom_Sales_Model_Cart->load($a->get('cart_id'))->set([
                    $prefix . 'firstname' => $a->firstname,
                    $prefix . 'lastname' => $a->lastname,
                    $prefix . 'company' => $a->company,
                    $prefix . 'attn' => $a->attn,
                    $prefix . 'street' => trim($a->street1 . "\n" . $a->street2 . "\n" . $a->street3),
                    $prefix . 'city' => $a->city,
                    $prefix . 'region' => $a->region,
                    $prefix . 'postcode' => $a->postcode,
                    $prefix . 'country' => $a->country,
                    $prefix . 'phone' => $a->phone,
                    $prefix . 'fax' => $a->fax,
                ])->save();
            }
            $this->BDb->ddlDropTable($tCartAddress);
        }

        $tOrderAddress = $this->FCom_Sales_Migrate_Model_Order_Address->table();
        if ($this->BDb->ddlTableExists($tOrderAddress)) {
            $addresses = $this->FCom_Sales_Migrate_Model_Order_Address->orm()->find_many();
            foreach ($addresses as $a) {
                $prefix = $a->get('atype') . '_';
                $this->FCom_Sales_Model_Order->load($a->get('order_id'))->set([
                    $prefix . 'firstname' => $a->firstname,
                    $prefix . 'lastname' => $a->lastname,
                    $prefix . 'company' => $a->company,
                    $prefix . 'attn' => $a->attn,
                    $prefix . 'street' => trim($a->street1 . "\n" . $a->street2 . "\n" . $a->street3),
                    $prefix . 'city' => $a->city,
                    $prefix . 'region' => $a->region,
                    $prefix . 'postcode' => $a->postcode,
                    $prefix . 'country' => $a->country,
                    $prefix . 'phone' => $a->phone,
                    $prefix . 'fax' => $a->fax,
                ])->save();
            }
            $this->BDb->ddlDropTable($tOrderAddress);
        }

        $this->BDb->ddlDropTable($this->BDb->t('fcom_sales_order_status'));
    }
}

class FCom_Sales_Migrate_Model_Cart_Address extends BModel
{
    protected static $_table = 'fcom_sales_cart_address';
}

class FCom_Sales_Migrate_Model_Order_Address extends BModel
{
    protected static $_table = 'fcom_sales_order_address';
}

