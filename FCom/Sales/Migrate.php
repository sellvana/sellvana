<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Migrate
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Catalog_Model_InventorySku $FCom_Catalog_Model_InventorySku
 * @property FCom_Sales_Migrate_Model_Cart_Address $FCom_Sales_Migrate_Model_Cart_Address
 * @property FCom_Sales_Migrate_Model_Order_Address $FCom_Sales_Migrate_Model_Order_Address
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Sales_Model_Cart_Item $FCom_Sales_Model_Cart_Item
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 * @property FCom_Sales_Model_Order_Comment $FCom_Sales_Model_Order_Comment
 * @property FCom_Sales_Model_Order_History $FCom_Sales_Model_Order_History
 * @property FCom_Sales_Model_Order_Item $FCom_Sales_Model_Order_Item
 * @property FCom_Sales_Model_Order_Payment $FCom_Sales_Model_Order_Payment
 * @property FCom_Sales_Model_Order_Payment_Item $FCom_Sales_Model_Order_Payment_Item
 * @property FCom_Sales_Model_Order_Refund $FCom_Sales_Model_Order_Refund
 * @property FCom_Sales_Model_Order_Refund_Item $FCom_Sales_Model_Order_Refund_Item
 * @property FCom_Sales_Model_Order_Return $FCom_Sales_Model_Order_Return
 * @property FCom_Sales_Model_Order_Return_Item $FCom_Sales_Model_Order_Return_Item
 * @property FCom_Sales_Model_Order_Shipment $FCom_Sales_Model_Order_Shipment
 * @property FCom_Sales_Model_Order_Shipment_Item $FCom_Sales_Model_Order_Shipment_Item
 * @property FCom_Sales_Model_Order_Cancel $FCom_Sales_Model_Order_Cancel
 * @property FCom_Sales_Model_Order_Cancel_Item $FCom_Sales_Model_Order_Cancel_Item
 * @property FCom_Sales_Model_StateCustom $FCom_Sales_Model_StateCustom
 */

class FCom_Sales_Migrate extends BClass
{

    public function install__0_3_7()
    {
        $tUser = $this->FCom_Admin_Model_User->table();
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $tInventorySku = $this->FCom_Catalog_Model_InventorySku->table();

        $tCart = $this->FCom_Sales_Model_Cart->table();
        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
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

        $tOrderCancel = $this->FCom_Sales_Model_Order_Cancel->table();
        $tOrderCancelItem = $this->FCom_Sales_Model_Order_Cancel_Item->table();

        $tOrderHistory = $this->FCom_Sales_Model_Order_History->table();

        $tOrderComment = $this->FCom_Sales_Model_Order_Comment->table();

        $tStateCustom = $this->FCom_Sales_Model_StateCustom->table();

        $this->BDb->ddlTableDef($tCart, [
            BDb::COLUMNS => [
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

                'state_overall' => "varchar(10) not null default ''",

                'billing_company' => 'varchar(50)',
                'billing_attn' => 'varchar(50)',
                'billing_firstname' => 'varchar(50)',
                'billing_lastname' => 'varchar(50)',
                'billing_street1' => 'varchar(255)',
                'billing_street2' => 'varchar(255)',
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
                'shipping_street1' => 'varchar(255)',
                'shipping_street2' => 'varchar(255)',
                'shipping_city' => 'varchar(50)',
                'shipping_region' => 'varchar(50)',
                'shipping_postcode' => 'varchar(20)',
                'shipping_country' => 'char(2)',
                'shipping_phone' => 'varchar(50)',
                'shipping_fax' => 'varchar(50)',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_cookie_token' => 'UNIQUE (cookie_token)',
                'customer_id' => "(`customer_id`)",
                'status' => "(`status`)",
            ],
        ]);

        $this->BDb->ddlTableDef($tCartItem, [
            BDb::COLUMNS => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'cart_id' => "int(10) unsigned DEFAULT NULL",
                'product_id' => "int(10) unsigned DEFAULT NULL",
                'product_sku' => "varchar(100) DEFAULT NULL",
                'inventory_id' => 'int unsigned default null',
                'inventory_sku' => 'varchar(100) default null',
                'product_name' => "varchar(255) DEFAULT NULL",
                'qty' => 'decimal(12,2) DEFAULT NULL',
                'price' => 'decimal(12,2) NOT NULL DEFAULT 0',
                'cost' => 'decimal(12,2) default null',
                'row_total' => "decimal(12,2) NULL",
                'row_tax' => "decimal(12,2) NOT NULL default 0",
                'row_discount' => "decimal(12,2) NOT NULL default 0",

                'promo_id_buy' => "VARCHAR(50) default NULL",
                'promo_id_get' => "INT(10) UNSIGNED default NULL",
                'promo_qty_used' => "decimal(12,2) DEFAULT NULL",
                'promo_amt_used' => "decimal(12,2) DEFAULT NULL",

                'parent_item_id' => 'int unsigned default null',
                'shipping_size' => 'varchar(30)',
                'shipping_weight' => 'decimal(12,2)',
                'pack_separate' => 'tinyint not null default 0',
                'show_separate' => 'tinyint not null default 0',
                'unique_hash' => 'bigint default null',

                'create_at' => "DATETIME NOT NULL",
                'update_at' => "DATETIME NOT NULL",
                'data_serialized' => "text  NULL",
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_cart_product_separate_hash' => '(cart_id, product_id, pack_separate, unique_hash)',
            ],
            BDb::CONSTRAINTS => [
                'cart' => ['cart_id', $tCart],
                'parent_item' => ['parent_item_id', $tCartItem],
                'product' => ['product_id', $tProduct, 'id', 'CASCADE', 'SET NULL'],
                'inventory' => ['inventory_id', $tInventorySku, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrder, [
            BDb::COLUMNS => [
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
                'shipping_price' => 'decimal(12,2) not null default 0',
                'tax_amount' => "decimal(12,2) DEFAULT NULL",
                'discount_amount' => 'decimal(12,2) default null',
                'create_at' => "datetime DEFAULT NULL",
                'update_at' => "datetime DEFAULT NULL",
                'grand_total' => "decimal(12,2) NOT NULL",
                'shipping_service_title' => "varchar(100) DEFAULT NULL",
                'data_serialized' => "text",
                'unique_id' => "varchar(15) NOT NULL",
                'admin_id' => "int(10) unsigned DEFAULT NULL",

                'same_address' => "tinyint(1) not null default 0",

                'billing_company' => 'varchar(50)',
                'billing_attn' => 'varchar(50)',
                'billing_firstname' => 'varchar(50)',
                'billing_lastname' => 'varchar(50)',
                'billing_street1' => 'varchar(255)',
                'billing_street2' => 'varchar(255)',
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
                'shipping_street1' => 'varchar(255)',
                'shipping_street2' => 'varchar(255)',
                'shipping_city' => 'varchar(50)',
                'shipping_region' => 'varchar(50)',
                'shipping_postcode' => 'varchar(20)',
                'shipping_country' => 'char(2)',
                'shipping_phone' => 'varchar(50)',
                'shipping_fax' => 'varchar(50)',

                'amount_paid' => 'decimal(12,2)',
                'amount_due' => 'decimal(12,2)',
                'amount_refunded' => 'decimal(12,2)',

                'state_overall' => "varchar(15) not null default 'new'",
                'state_delivery' => "varchar(15) not null default 'pending'",
                'state_payment' => "varchar(15) not null default 'new'",
                'state_custom' => "varchar(15) not null default ''",

            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_cart_id' => 'UNIQUE (cart_id)',
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderItem, [
            BDb::COLUMNS => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'order_id' => "int(10) unsigned DEFAULT NULL",
                'cart_item_id' => 'int unsigned default null',
                'parent_item_id' => 'int unsigned default null',
                'product_id' => "int(10) unsigned DEFAULT NULL",
                'product_sku' => 'varchar(100) default null',
                'inventory_id' => 'int unsigned default null',
                'inventory_sku' => 'varchar(100) default null',
                'product_name' => 'varchar(255) default null',
                'price' => 'decimal(12,2) not null default 0',
                'cost' => 'decimal(12,2) default null',
                'row_total' => 'decimal(12,2) NOT NULL DEFAULT 0',
                'row_tax' => 'decimal(12,2) not null default 0',
                'row_discount' => 'decimal(12,2) not null default 0',

                'data_serialized' => 'text null',

                'shipping_size' => 'varchar(30)',
                'shipping_weight' => 'decimal(12,2)',
                'pack_separate' => 'tinyint not null default 0',
                'show_separate' => 'tinyint not null default 0',

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
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'cart' => ['order_id', $tOrder],
                'parent_item' => ['parent_item_id', $tOrderItem],
                'product' => ['product_id', $tProduct, 'id', 'CASCADE', 'SET NULL'],
                'inventory' => ['inventory_id', $tInventorySku, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderShipment, [
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderShipmentItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned not null',
                'shipment_id' => 'int unsigned not null',
                'qty' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'order_item' => ['order_item_id', $tOrderItem],
                'shipment' => ['shipment_id', $tOrderShipment],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderPayment, [
            BDb::COLUMNS => [
                'id'               => 'int (10) unsigned not null auto_increment',
                'order_id'         => 'int unsigned not null',
                'create_at'        => 'datetime not null',
                'update_at'        => 'datetime null',
                'payment_method'   => 'varchar(50) not null',
                'parent_id'        => 'int(10) null',
                'amount_authorized' => 'decimal(12,2)',
                'amount_due'       => 'decimal(12,2)',
                'amount_captured'  => 'decimal(12,2)',
                'amount_refunded'  => 'decimal(12,2)',
                'data_serialized'  => 'text',
                'transaction_status' => 'varchar(50)',
                'transaction_id'   => 'varchar(50)',
                'transaction_type' => 'varchar(50)',
                'transaction_fee'  => 'decimal(12,2)',
                'online'           => 'BOOL',
                'state_overall'    => "varchar(20) not null default 'new'",
                'state_custom'     => "varchar(20) not null default ''",
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS  => [
                'IDX_method_status' => '(payment_method, transaction_status)',
                'IDX_order'         => '(order_id)',
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
                'IDX_transaction_id' => '(transaction_id)',
                'IDX_transaction_type' => '(transaction_type)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder, 'id', 'CASCADE', 'RESTRICT'],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderPaymentItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'order_item_id' => 'int unsigned not null',
                'payment_id' => 'int unsigned not null',
                'qty' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'order_item' => ['order_item_id', $tOrderItem],
                'payment' => ['payment_id', $tOrderPayment],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderReturn, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'state_overall' => "varchar(10) not null default 'new'",
                'state_custom' => "varchar(10) not null default ''",
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderReturnItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned not null',
                'return_id' => 'int unsigned not null',
                'qty' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'order_item' => ['order_item_id', $tOrderItem],
                'return' => ['return_id', $tOrderReturn],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderRefund, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'state_overall' => "varchar(10) not null default 'new'",
                'state_custom' => "varchar(10) not null default ''",
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderRefundItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned not null',
                'refund_id' => 'int unsigned not null',
                'qty' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'order_item' => ['order_item_id', $tOrderItem],
                'refund' => ['refund_id', $tOrderRefund],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderCancel, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'state_overall' => "varchar(10) not null default 'new'",
                'state_custom' => "varchar(10) not null default ''",
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderCancelItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned not null',
                'cancel_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned not null',
                'qty' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'cancel' => ['cancel_id', $tOrderCancel],
                'order_item' => ['order_item_id', $tOrderItem],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderHistory, [
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_event_at' => '(event_at)',
                'IDX_order_id' => '(order_id, event_at)',
                'IDX_entity_type_id' => '(entity_type, entity_id, event_at)',
                'IDX_event_type_at' => '(event_type, event_at)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'order_item' => ['order_item_id', $tOrderItem, 'id', 'CASCADE', 'SET NULL'],
                'user' => ['user_id', $tUser, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderComment, [
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_order_create' => '(order_id, create_at)',
                'IDX_admin_user' => '(from_admin, user_id)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'user' => ['user_id', $tUser, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);

        $this->BDb->ddlTableDef($tStateCustom, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'entity_type' => 'varchar(15) not null',
                'state_code' => 'varchar(20) not null',
                'state_label' => 'varchar(50) not null',
                'concrete_class' => 'varchar(100) null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($tOrder, [
            BDb::COLUMNS => [
                'created_dt' => 'datetime NULL',
                'purchased_dt' => 'datetime NULL',
            ]
        ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($tOrder, [
            BDb::COLUMNS => [
                'gt_base' => 'decimal(10,2) NOT NULL',
            ],
        ]);
    }

    public function upgrade__0_1_3__0_1_4()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($tOrder, [BDb::COLUMNS => [
            'status' => "enum('new', 'paid', 'pending') not null default 'new'",
        ]]);
    }

    public function upgrade__0_1_4__0_1_5()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($tOrder, [BDb::COLUMNS => [
            'shipping_service_title' => "varchar(100) not null default ''"
        ]]);
    }

    public function upgrade__0_1_5__0_1_6()
    {
        /*
        $tStatus = $this->FCom_Sales_Model_Order_CustomStatus->table();
        $this->BDb->ddlTableDef($tStatus, [
            BDb::COLUMNS => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'name' => "varchar(50) NOT NULL DEFAULT '' ",
                'code' => "varchar(50) NOT NULL DEFAULT ''",
            ],
            BDb::PRIMARY => '(`id`)',
        ]);
        */
    }

    public function upgrade__0_1_6__0_1_7()
    {
        /*
        $tStatus = $this->FCom_Sales_Model_Order_CustomStatus->table();
        $this->BDb->run("
            insert into {$tStatus}(id,name,code) values(1, 'New', 'new'),(2,'Pending','pending'),(3,'Paid','paid')
        ");
        */
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
        /*
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order_Address->table(), [
            BDb::COLUMNS => [
                'state' => 'RENAME region varchar(50)',
                'zip' => 'RENAME postcode varchar(20)',
            ],
        ]);
        */
    }

    public function upgrade__0_1_10__0_2_0()
    {

        $tCart = $this->FCom_Sales_Model_Cart->table();
        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
        //$tCartAddress = $this->FCom_Sales_Model_Cart_Address->table();

        $this->BDb->ddlTableDef($tCart, [
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(`id`)',
            BDb::KEYS => [
                'session_id' => "UNIQUE (`session_id`)",
                'UNQ_user_id' => "UNIQUE (`user_id`,`description`,`session_id`)",
                'company_id' => "(`company_id`)",
            ],
        ]);

        $this->BDb->ddlTableDef($tCartItem, [
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(`id`)',
            BDb::KEYS => [
                'UNQ_cart_id' => "UNIQUE (`cart_id`,`product_id`)",
            ],
            BDb::CONSTRAINTS => [
                'cart' => ['cart_id', $tCart],
            ],
        ]);
        /*
        $this->BDb->ddlTableDef($tCartAddress, [
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(`id`)',
            BDb::CONSTRAINTS => [
                'cart' => ['cart_id', $tCart],
            ],
        ]);
        */
    }

    public function upgrade__0_2_0__0_2_1()
    {
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
            BDb::KEYS => [
                'NewIndex1' => BDb::DROP,
                'user_id' => BDb::DROP,
            ],
        ]);
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
            BDb::COLUMNS => [
                'data_serialized' => 'text',
                'company_id' => BDb::DROP,
                'location_id' => BDb::DROP,
                'description' => BDb::DROP,
                'user_id' => BDb::DROP,
                'totals_json' => BDb::DROP,
                'calc_balance' => BDb::DROP,
                'sort_order' => BDb::DROP,
                'discount_code' => 'RENAME coupon_code varchar(50) null',
                'customer_id' => 'int unsigned not null after session_id',
                'customer_email' => 'varchar(100) null after customer_id',
                'tax_amount' => 'decimal(12,2) not null default 0 after subtotal',
                'discount_amount' => 'decimal(12,2) not null default 0 after tax_amount',
                'grand_total' => 'decimal(12,2) not null default 0 after discount_amount',
                'status' => "varchar(10) not null default 'new'",
            ],
            BDb::KEYS => [
                'session_id' => '(session_id)',
                'customer_id' => '(customer_id)',
                'status' => '(status)',
            ],
        ]);

        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart_Item->table(), [
            BDb::COLUMNS => [
                'product_sku' => 'varchar(100) null after product_id',
                'product_name' => 'varchar(255) null after product_sku',
                'tax' => 'decimal(12,2) not null default 0 after rowtotal',
                'discount' => 'decimal(12,2) not null default 0 after tax',
                'data_serialized' => 'text after update_dt',
            ],
        ]);
        /*
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart_Address->table(), [
            BDb::COLUMNS => [
                'state' => 'RENAME region varchar(50)',
                'zip' => 'RENAME postcode varchar(20)',
            ],
        ]);
        */
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order->table(), [
            BDb::COLUMNS => [
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
            BDb::COLUMNS => [
                'last_calc_at' => 'int unsigned',
            ],
        ]);
    }

    public function upgrade__0_2_2__0_2_3()
    {
        /*
        if (!$this->BDb->ddlTableExists('fcom_sales_order_address')) {
            $this->BDb->run("
                RENAME TABLE fcom_sales_address TO fcom_sales_order_address;
            ");
        }
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart_Address->table(), [
            BDb::COLUMNS => [
                'middle_initial' => 'VARCHAR(2) NULL AFTER lastname',
                'prefix' => 'VARCHAR(10) NULL AFTER middle_initial',
                'suffix' => 'VARCHAR(10) NULL AFTER prefix',
                'company' => 'VARCHAR(50) NULL AFTER suffix',
            ],
        ]);
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order_Address->table(), [
            BDb::COLUMNS => [
                'middle_initial' => 'VARCHAR(2) NULL AFTER lastname',
                'prefix' => 'VARCHAR(10) NULL AFTER middle_initial',
                'suffix' => 'VARCHAR(10) NULL AFTER prefix',
                'company' => 'VARCHAR(50) NULL AFTER suffix',
            ],
        ]);

        */
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order->table(), [
            BDb::COLUMNS => [
                'customer_email' => 'VARCHAR(100) NULL AFTER customer_id',
            ],
        ]);
    }

    public function upgrade__0_2_3__0_2_4()
    {
        // todo update created at fields

        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order->table(), [
            BDb::COLUMNS => [
                'created_dt' => 'RENAME created_at datetime DEFAULT NULL',
                'purchased_dt' => 'RENAME updated_at datetime DEFAULT NULL',
                'gt_base' => 'RENAME grandtotal decimal(12,2) NOT NULL',
                'tax' => 'decimal(10,2) NULL',
                'unique_id' => 'varchar(15) NOT NULL',
                'status' => 'varchar(50) NOT NULL',
                'shippping_service' => BDb::DROP,
                'payment_details' => BDb::DROP,
                'status_id' => BDb::DROP,
                'totals_json' => BDb::DROP,
            ],
        ]);
    }

    public function upgrade__0_2_4__0_2_5()
    {
        foreach ([$this->FCom_Sales_Model_Cart_Item->table(),
           //$this->FCom_Sales_Model_Cart_Address->table(),
           //$this->FCom_Sales_Model_Order_Address->table(),
        ] as $table) {
            $this->BDb->ddlTableDef($table, [
                BDb::COLUMNS => [
                    'create_dt' => 'RENAME create_at datetime NOT NULL',
                    'update_dt' => 'RENAME update_at datetime NOT NULL',
                ],
            ]);
        }
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
            BDb::COLUMNS => [
                'create_dt' => 'RENAME create_at datetime NULL',
                'update_dt' => 'RENAME update_at datetime NULL',
            ],
        ]);
    }

    public function upgrade__0_2_5__0_2_6()
    {

        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order->table(), [
            BDb::COLUMNS => [
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
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(id)',
            BDb::KEYS  => [
                'method'           => '(method)',
                'order_id'         => '(order_id)',
                'status'           => '(status)',
                'transaction_id'   => '(transaction_id)',
                'transaction_type' => '(transaction_type)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder, 'id', 'CASCADE', 'RESTRICT'],
            ],
        ]);
    }

    public function upgrade__0_2_7__0_2_8()
    {
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Order->table(), [
                BDb::COLUMNS => [
                    'admin_id' => 'int(10) unsigned NOT NULL',
                ],
            ]);

        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
                BDb::COLUMNS => [
                    'admin_id' => 'int(10) unsigned NOT NULL',
                ],
            ]);
    }

    public function upgrade__0_2_8__0_2_9()
    {
        $this->BDb->ddlTableDef($this->FCom_Sales_Model_Cart->table(), [
            BDb::COLUMNS => [
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
            BDb::COLUMNS => [
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
            BDb::COLUMNS => [
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
            BDb::COLUMNS => [
                'session_id' => BDb::DROP,
                'cookie_token' => 'varchar(40) default null after grand_total',
                'status' => "varchar(10) default 'new'",
            ],
            BDb::KEYS => [
                'session_id' => BDb::DROP,
                'UNQ_cookie_token' => 'UNIQUE (cookie_token)',
                'IDX_status_cookie_token' => '(`status`, cookie_token)',
            ],
        ]);
    }

    public function upgrade__0_2_12__0_2_13()
    {
        $tCart = $this->FCom_Sales_Model_Cart->table();
        $this->BDb->ddlTableDef($tCart, [
            BDb::COLUMNS => [
                'same_address' => "tinyint(1) not null default 0",
            ],
        ]);

        $tOrder= $this->FCom_Sales_Model_Order->table();
        $this->BDb->ddlTableDef($tOrder, [
            BDb::COLUMNS => [
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
            BDb::COLUMNS => [
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
            BDb::COLUMNS => [
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
            BDb::COLUMNS => [
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
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderShipmentItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'order_item' => ['order_item_id', $tOrderItem],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderPayment, [
            BDb::COLUMNS => [
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
            BDb::KEYS  => [
                'method'           => BDb::DROP,
                'order_id'         => BDb::DROP,
                'status'           => BDb::DROP,
                'transaction_id'   => BDb::DROP,
                'transaction_type' => BDb::DROP,

                'IDX_method_status' => '(payment_method, transaction_status)',
                'IDX_order'         => '(order_id)',
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
                'IDX_transaction_id' => '(transaction_id)',
                'IDX_transaction_type' => '(transaction_type)',
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderPaymentItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'order_item_id' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'order_item' => ['order_item_id', $tOrderItem],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderReturn, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'state_overall' => "varchar(10) not null default 'new'",
                'state_custom' => "varchar(10) not null default ''",
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderReturnItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'order_item' => ['order_item_id', $tOrderItem],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderRefund, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'state_overall' => "varchar(10) not null default 'new'",
                'state_custom' => "varchar(10) not null default ''",
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderRefundItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'order_item' => ['order_item_id', $tOrderItem],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderHistory, [
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_event_at' => '(event_at)',
                'IDX_order_id' => '(order_id, event_at)',
                'IDX_entity_type_id' => '(entity_type, entity_id, event_at)',
                'IDX_event_type_at' => '(event_type, event_at)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'order_item' => ['order_item_id', $tOrderItem, 'id', 'CASCADE', 'SET NULL'],
                'user' => ['user_id', $tUser, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderComment, [
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_order_create' => '(order_id, create_at)',
                'IDX_admin_user' => '(from_admin, user_id)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'user' => ['user_id', $tUser, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);

        $this->BDb->ddlTableDef($tStateCustom, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'entity_type' => 'varchar(15) not null',
                'state_code' => 'varchar(20) not null',
                'state_label' => 'varchar(50) not null',
                'concrete_class' => 'varchar(100) null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
        ]);

        $this->BDb->ddlDropTable($this->BDb->t('fcom_sales_order_status'));
    }

    public function upgrade__0_3_0__0_3_1()
    {
        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
        $tOrderItem = $this->FCom_Sales_Model_Order_Item->table();

        $this->BDb->ddlTableDef($tCartItem, [
            BDb::COLUMNS => [
                'product_sku' => 'varchar(100) not null',
                'inventory_sku' => 'varchar(100) default null after product_sku',
                'parent_item_id' => 'int unsigned default null',
                'shipping_size' => 'varchar(30)',
                'shipping_weight' => 'decimal(12,2)',
                'pack_separate' => 'tinyint not null default 0',
                'unique_hash' => 'bigint default null',
            ],
            BDb::KEYS => [
                'cart_id' => BDb::DROP,
                'IDX_cart_product_separate_hash' => '(cart_id, product_id, pack_separate, unique_hash)',
            ],
            BDb::CONSTRAINTS => [
                'parent_item' => ['parent_item_id', $tCartItem],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderItem, [
            BDb::COLUMNS => [
                'cart_item_id' => 'int unsigned default null',
                'product_sku' => 'varchar(100) default null',
                'stock_sku' => 'varchar(100) default null',
                'parent_item_id' => 'int unsigned default null',
                'shipping_size' => 'varchar(30)',
                'shipping_weight' => 'decimal(12,2)',
            ],
            BDb::CONSTRAINTS => [
                'parent_item' => ['parent_item_id', $tOrderItem],
            ],
        ]);
    }

    public function upgrade__0_3_1__0_3_2()
    {
        $tOrder             = $this->FCom_Sales_Model_Order->table();
        $tOrderItem         = $this->FCom_Sales_Model_Order_Item->table();
        $tOrderCancel       = $this->FCom_Sales_Model_Order_Cancel->table();
        $tOrderCancelItem   = $this->FCom_Sales_Model_Order_Cancel_Item->table();
        $tOrderPayment      = $this->FCom_Sales_Model_Order_Payment->table();
        $tOrderPaymentItem  = $this->FCom_Sales_Model_Order_Payment_Item->table();
        $tOrderShipment     = $this->FCom_Sales_Model_Order_Shipment->table();
        $tOrderShipmentItem = $this->FCom_Sales_Model_Order_Shipment_Item->table();
        $tOrderReturn       = $this->FCom_Sales_Model_Order_Return->table();
        $tOrderReturnItem   = $this->FCom_Sales_Model_Order_Return_Item->table();
        $tOrderRefund       = $this->FCom_Sales_Model_Order_Refund->table();
        $tOrderRefundItem   = $this->FCom_Sales_Model_Order_Refund_Item->table();

        $this->BDb->ddlTableDef($tOrderCancel, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned default null',
                'state_overall' => "varchar(10) not null default 'new'",
                'state_custom' => "varchar(10) not null default ''",
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_state_overall' => '(state_overall)',
                'IDX_state_custom' => '(state_custom)',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderCancelItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned not null',
                'cancel_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned not null',
                'qty' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'cancel' => ['cancel_id', $tOrderCancel],
                'order_item' => ['order_item_id', $tOrderItem],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderPaymentItem, [
            BDb::COLUMNS => [
                'payment_id' => 'int unsigned not null after order_id',
                'qty' => 'int unsigned not null after payment_id',
            ],
            BDb::CONSTRAINTS => [
                'payment' => ['payment_id', $tOrderPayment],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderShipmentItem, [
            BDb::COLUMNS => [
                'shipment_id' => 'int unsigned not null after order_id',
                'qty' => 'int unsigned not null after shipment_id',
            ],
            BDb::CONSTRAINTS => [
                'shipment' => ['shipment_id', $tOrderShipment],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderReturnItem, [
            BDb::COLUMNS => [
                'return_id' => 'int unsigned not null after order_id',
                'qty' => 'int unsigned not null after return_id',
            ],
            BDb::CONSTRAINTS => [
                'return' => ['return_id', $tOrderReturn],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderRefundItem, [
            BDb::COLUMNS => [
                'refund_id' => 'int unsigned not null after order_id',
                'qty' => 'int unsigned not null after refund_id',
            ],
            BDb::CONSTRAINTS => [
                'refund' => ['refund_id', $tOrderRefund],
            ],
        ]);
    }

    public function upgrade__0_3_3__0_3_4()
    {
        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
        $tOrderItem = $this->FCom_Sales_Model_Order_Item->table();

        $this->BDb->ddlTableDef($tCartItem, [
            'COLUMNS' => [
                'inventory_id' => 'int unsigned default null',
                'stock_sku' => 'RENAME inventory_sku varchar(100) default null',
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderItem, [
            BDb::COLUMNS => [
                'inventory_id' => 'int unsigned default null',
                'local_sku' => 'RENAME product_sku varchar(100) default null',
                'stock_sku' => 'RENAME inventory_sku varchar(100) default null',
            ],
        ]);
    }

    public function upgrade__0_3_4__0_3_5()
    {
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $tInventorySku = $this->FCom_Catalog_Model_InventorySku->table();

        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
        $tOrderItem = $this->FCom_Sales_Model_Order_Item->table();

        $this->BDb->ddlTableDef($tCartItem, [
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct, 'id', 'CASCADE', 'SET NULL'],
                'inventory' => ['inventory_id', $tInventorySku, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderItem, [
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct, 'id', 'CASCADE', 'SET NULL'],
                'inventory' => ['inventory_id', $tInventorySku, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);
    }

    public function upgrade__0_3_5__0_3_6()
    {
        $tCart = $this->FCom_Sales_Model_Cart->table();
        $tOrder = $this->FCom_Sales_Model_Order->table();

        $this->BDb->ddlTableDef($tCart, [
            BDb::COLUMNS => [
                'shipping_street' => 'RENAME shipping_street1 varchar(255)',
                'shipping_street2' => 'varchar(255) AFTER shipping_street1',
                'billing_street' => 'RENAME billing_street1 varchar(255)',
                'billing_street2' => 'varchar(255) AFTER billing_street1',
            ],
        ]);

        $this->BDb->ddlTableDef($tOrder, [
            BDb::COLUMNS => [
                'shipping_street' => 'RENAME shipping_street1 varchar(255)',
                'shipping_street2' => 'varchar(255) AFTER shipping_street1',
                'billing_street' => 'RENAME billing_street1 varchar(255)',
                'billing_street2' => 'varchar(255) AFTER billing_street1',
            ],
        ]);
    }

    public function upgrade__0_3_6__0_3_7()
    {
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
        $tOrderItem = $this->FCom_Sales_Model_Order_Item->table();

        $this->BDb->ddlTableDef($tOrder, [
            BDb::COLUMNS => [
                'grandtotal' => 'RENAME grand_total decimal(12,2) not null default 0',
                'tax' => 'RENAME tax_amount decimal(12,2) not null default 0',
                'discount_amount' => 'decimal(12,2) not null default 0',
                'shipping_price' => 'decimal(12,2) not null default 0',
                'balance' => BDb::DROP,
            ],
            BDb::KEYS => [
                'UNQ_cart_id' => BDb::DROP,
            ],
        ]);

        $this->BDb->ddlTableDef($tCartItem, [
            BDb::COLUMNS => [
                'rowtotal' => 'RENAME row_total decimal(12,2) not null default 0',
                'tax' => 'RENAME row_tax decimal(12,2) not null default 0',
                'discount' => 'RENAME row_discount decimal(12,2) not null default 0',
                'show_separate' => 'tinyint not null default 0',
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderItem, [
            BDb::COLUMNS => [
                'product_info' => BDb::DROP,
                'product_name' => 'varchar(255) default null',
                'price' => 'decimal(12,2) not null default 0',
                'total' => 'RENAME row_total decimal(12,2) not null default 0',
                'row_tax' => 'decimal(12,2) not null default 0',
                'row_discount' => 'decimal(12,2) not null default 0',
                'show_separate' => 'tinyint not null default 0',
                'pack_separate' => 'tinyint not null default 0',
            ],
        ]);
    }

    public function upgrade__0_3_7__0_3_8()
    {
        $tCart = $this->FCom_Sales_Model_Cart->table();
        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
        $tOrderItem = $this->FCom_Sales_Model_Order_Item->table();

        $this->BDb->ddlTableDef($tCart, [
            BDb::COLUMNS => [
                'recalc_shipping_rates' => 'tinyint not null default 0',
            ],
        ]);

        $this->BDb->ddlTableDef($tCartItem, [
            BDb::COLUMNS => [
                'cost' => 'decimal(12,2) default null',
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderItem, [
            BDb::COLUMNS => [
                'cost' => 'decimal(12,2) default null',
            ],
        ]);
    }

    public function upgrade__0_3_8__0_3_9()
    {
        $tCart = $this->FCom_Sales_Model_Cart->table();
        $tOrderPayment = $this->FCom_Sales_Model_Order_Payment->table();

        $this->BDb->ddlTableDef($tCart, [
            BDb::COLUMNS => [
                'state_payment' => 'varchar(20)',
            ],
            BDb::KEYS => [
                'IDX_state_payment' => '(state_payment)',
            ],
        ]);

        $this->BDb->ddlTableDef($tOrderPayment, [
            BDb::COLUMNS => [
                'transaction_token' => 'varchar(50)',
            ],
            BDb::KEYS => [
                'IDX_transaction_token' => '(transaction_token)',
            ],
        ]);
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

