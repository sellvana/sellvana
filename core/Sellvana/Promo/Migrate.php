<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @property Sellvana_Promo_Model_PromoCoupon    $Sellvana_Promo_Model_PromoCoupon
 * @property Sellvana_Promo_Model_Promo          $Sellvana_Promo_Model_Promo
 * @property Sellvana_Promo_Model_PromoHistory   $Sellvana_Promo_Model_PromoHistory
 * @property Sellvana_Promo_Model_PromoMedia     $Sellvana_Promo_Model_PromoMedia
 * @property Sellvana_Promo_Model_PromoProduct   $Sellvana_Promo_Model_PromoProduct
 * @property Sellvana_Promo_Model_PromoProductPrice $Sellvana_Promo_Model_PromoProductPrice
 * @property Sellvana_Promo_Model_PromoCart      $Sellvana_Promo_Model_PromoCart
 * @property Sellvana_Promo_Model_PromoCartItem  $Sellvana_Promo_Model_PromoCartItem
 * @property Sellvana_Promo_Model_PromoOrder     $Sellvana_Promo_Model_PromoOrder
 * @property Sellvana_Customer_Model_Customer    $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_Cart           $Sellvana_Sales_Model_Cart
 * @property Sellvana_Sales_Model_Cart_Item      $Sellvana_Sales_Model_Cart_Item
 * @property Sellvana_Sales_Model_Order          $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Item     $Sellvana_Sales_Model_Order_Item
 * @property Sellvana_Catalog_Model_Product      $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_Promo_Model_PromoDisplay   $Sellvana_Promo_Model_PromoDisplay
 * @property FCom_Admin_Model_User               $FCom_Admin_Model_User
 */
class Sellvana_Promo_Migrate extends BClass
{
    public function install__0_2_2()
    {
        $tAdminUser     = $this->FCom_Admin_Model_User->table();
        $tCustomer      = $this->Sellvana_Customer_Model_Customer->table();
        $tProduct       = $this->Sellvana_Catalog_Model_Product->table();
        $tProductPrice  = $this->Sellvana_Catalog_Model_ProductPrice->table();
        $tCart          = $this->Sellvana_Sales_Model_Cart->table();
        $tCartItem      = $this->Sellvana_Sales_Model_Cart_Item->table();
        $tOrder         = $this->Sellvana_Sales_Model_Order->table();
        $tOrderItem     = $this->Sellvana_Sales_Model_Order_Item->table();
        $tPromo         = $this->Sellvana_Promo_Model_Promo->table();
        $tPromoCoupon   = $this->Sellvana_Promo_Model_PromoCoupon->table();
        $tPromoOrder    = $this->Sellvana_Promo_Model_PromoOrder->table();
        $tPromoCart     = $this->Sellvana_Promo_Model_PromoCart->table();
        $tPromoMedia    = $this->Sellvana_Promo_Model_PromoMedia->table();
        $tPromoProduct  = $this->Sellvana_Promo_Model_PromoProduct->table();
        $tPromoProductPrice = $this->Sellvana_Promo_Model_PromoProductPrice->table();
        $tPromoHistory  = $this->Sellvana_Promo_Model_PromoHistory->table();
        $tPromoDisplay  = $this->Sellvana_Promo_Model_PromoDisplay->table();
        $tPromoCartItem = $this->Sellvana_Promo_Model_PromoCartItem->table();

        $this->BDb->ddlTableDef($tPromo, [
            BDb::COLUMNS => [
                'id'                    => 'int unsigned not null auto_increment',

                'from_date'             => 'date null',
                'to_date'               => 'date null',
                'status'                => "enum('template','pending','active','expired')  not null  default 'pending'",

                'summary'               => "VARCHAR(255) NOT NULL",
                'internal_notes'        => "TEXT NULL",
                'customer_label'        => "VARCHAR(255) NULL",
                'customer_details'      => "TEXT NULL",

                'promo_type'            => "ENUM('catalog','cart') NOT NULL DEFAULT 'cart'",
                'coupon_type'           => 'TINYINT UNSIGNED DEFAULT 0 COMMENT "0 = Do not use, 1 = Single code, 2 = multiple codes"',

                'limit_per_promo'       => "INT(10) UNSIGNED NULL DEFAULT 0",
                'limit_per_customer'    => "INT(10) UNSIGNED NULL DEFAULT 0",
                'limit_per_coupon'      => "INT(10) UNSIGNED NULL DEFAULT 1",

                'site_ids'              => 'varchar(255)',
                'customer_group_ids'    => 'varchar(255)',

                'priority_order'        => 'smallint not null default 0',
                'stop_flag'             => 'tinyint not null default 0',

                'display_index'         => 'tinyint not null default 0',
                'display_index_order'   => 'int unsigned not null default 0',
                'display_index_section' => "varchar(10) not null default 'regular'",
                'display_index_type'    => "varchar(10) not null default 'text'",
                'display_index_showexp' => 'tinyint unsigned not null default 1',

                'conditions_operator'   => "varchar(6) not null default 'always'",

                'data_serialized'       => "TEXT",
                'create_at'             => 'datetime not null',
                'update_at'             => 'datetime null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_promo_status'              => "(status)",
                'IDX_promo_from_to_date'        => "(from_date, to_date)",
                'IDX_status_type_priority_date' => '(`status`, promo_type, coupon_type, priority_order, from_date, to_date)',
                'IDX_display_index'             => '(display_index, display_index_order)',
            ]
        ]);

        $this->BDb->ddlTableDef($tPromoMedia, [
            BDb::COLUMNS => [
                'id'            => 'int unsigned not null auto_increment',
                'promo_id'      => 'int unsigned null',
                'file_id'       => 'int unsigned not null',
                'promo_status'  => "char(1) not null default 'A'",
            ],
            BDb::PRIMARY => '(id)',
        ]);

        $this->BDb->ddlTableDef($tPromoCoupon, [
            BDb::COLUMNS => [
                'id'                => "INT(10) UNSIGNED NOT NULL AUTO_INCREMENT",
                'promo_id'          => "INT(10) UNSIGNED NOT NULL",
                'code'              => "VARCHAR(50) NOT NULL",
                'uses_per_customer' => "INT(10) UNSIGNED NULL ",
                'uses_total'        => "INT(10) UNSIGNED NULL ",
                'total_used'        => "INT(10) UNSIGNED NULL",
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                "promo" => ["promo_id", $tPromo],
            ]
        ]);

        $this->BDb->ddlTableDef($tPromoCart, [
            BDb::COLUMNS => [
                'id'                => "INT(10) unsigned not null auto_increment",
                'promo_id'          => "INT(10) UNSIGNED NOT NULL",
                'cart_id'           => "INT(10) UNSIGNED NOT NULL",
                'coupon_id'         => "INT(10) UNSIGNED NULL",
                'free_cart_item_id' => "INT(10) UNSIGNED NULL",
                'coupon_code'       => "varchar(50) NULL",
                'subtotal_discount' => "DECIMAL(12,2) NULL",
                'shipping_discount' => "DECIMAL(12,2) NULL",
                'create_at'         => "DATETIME NOT NULL",
                'update_at'         => "DATETIME NULL",
                'customer_id'       => 'int unsigned',
                'data_serialized'   => 'text',
                'shipping_free'     => 'tinyint not null default 0',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                "promo"     => ["promo_id", $tPromo],
                "cart"      => ["cart_id", $tCart],
                'cart_item' => ['free_cart_item_id', $tCartItem],
                'customer'  => ['customer_id', $tCustomer, 'id', 'CASCADE', 'SET NULL'],
            ]
        ]);

        $this->BDb->ddlTableDef($tPromoOrder, [
            BDb::COLUMNS => [
                'id'                => "INT(10) unsigned not null auto_increment",
                'promo_id'          => "INT(10) UNSIGNED NOT NULL",
                'order_id'          => "INT(10) UNSIGNED NULL",
                'coupon_id'         => "INT(10) UNSIGNED NULL",
                'free_order_item_id' => "INT(10) UNSIGNED NULL",
                'coupon_code'       => "varchar(50) NULL",
                'subtotal_discount' => "DECIMAL(12,2) NULL",
                'shipping_discount' => "DECIMAL(12,2) NULL",
                'create_at'         => "DATETIME NOT NULL",
                'update_at'         => "DATETIME NULL",
                'customer_id'       => 'int unsigned',
                'data_serialized'   => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                "promo"      => ["promo_id", $tPromo],
                "order"      => ["order_id", $tOrder],
                'order_item' => ['free_order_item_id', $tOrderItem],
                'customer'   => ['customer_id', $tCustomer, 'id', 'CASCADE', 'SET NULL'],
            ]
        ]);

        $this->BDb->ddlTableDef($tPromoHistory, [
            BDb::COLUMNS => [
                'id'              => 'int unsigned not null auto_increment',
                'promo_id'        => 'int unsigned not null',
                'action_type'     => 'varchar(20) not null',
                'coupon_id'       => 'int unsigned',
                'coupon_code'     => 'varchar(50)',
                'admin_user_id'   => 'int unsigned',
                'customer_id'     => 'int unsigned',
                'cart_id'         => 'int unsigned',
                'order_id'        => 'int unsigned',
                'create_at'       => 'datetime not null',
                'update_at'       => 'datetime',
                'description'     => 'text',
                'data_serialized' => 'text',
                'amount'          => 'decimal(12,2)',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'promo'      => ['promo_id', $tPromo],
                'coupon'     => ['coupon_id', $tPromoCoupon],
                'admin_user' => ['admin_user_id', $tAdminUser],
                'customer'   => ['customer_id', $tCustomer],
                'cart'       => ['cart_id', $tCart],
                'order'      => ['order_id', $tOrder],
            ],
        ]);

        $this->BDb->ddlTableDef($tPromoDisplay, [
            BDb::COLUMNS     => [
                'id'              => 'int unsigned not null auto_increment',
                'promo_id'        => 'int unsigned not null',
                'page_type'       => 'varchar(50) not null default "home_page"',
                'page_location'   => 'varchar(50) not null default ""',
                'content_type'    => 'varchar(20) not null default "html"',
                'data_serialized' => 'text',
                'create_at'       => 'datetime not null',
                'update_at'       => 'datetime',
            ],
            BDb::PRIMARY     => '(id)',
            BDb::CONSTRAINTS => [
                'promo' => ['promo_id', $tPromo],
            ]
        ]);

        $this->BDb->ddlTableDef($tPromoCartItem, [
            BDb::COLUMNS => [
                'id'              => 'int unsigned not null auto_increment',
                'promo_cart_id'   => 'int unsigned not null',
                'promo_id'        => 'int unsigned not null',
                'cart_id'         => 'int unsigned not null',
                'cart_item_id'    => 'int unsigned not null',
                'item_type'       => 'tinyint not null default 0',
                'data_serialized' => 'text',
                'create_at'       => 'datetime',
                'update_at'       => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_promo_cart_item' => '(promo_id, cart_id, cart_item_id, item_type)',
            ],
            BDb::CONSTRAINTS => [
                'promo_cart' => ['promo_cart_id', $tPromoCart],
                'promo'      => ['promo_id', $tPromo],
                'cart'       => ['cart_id', $tCart],
                'cart_item'  => ['cart_item_id', $tCartItem],
            ],
        ]);

        /*
        $this->BDb->ddlTableDef($tPromoProduct, [
            BDb::COLUMNS => [
                'id'              => 'int unsigned not null auto_increment',
                'promo_id'        => 'int unsigned not null',
                'product_id'      => 'int unsigned not null',
                'calc_status'     => 'tinyint not null default 0',
                'data_serialized' => 'text',
                'create_at'       => 'datetime',
                'update_at'       => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_calc_status' => '(calc_status)',
            ],
            BDb::CONSTRAINTS => [
                'promo'   => ['promo_id', $tPromo],
                'product' => ['product_id', $tProduct],
            ],
        ]);

        $this->BDb->ddlTableDef($tPromoProductPrice, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'promo_id' => 'int unsigned not null',
                'product_id' => 'int unsigned not null',
                'product_price_id' => 'int unsigned not null',
                'promo_product_id' => 'int unsigned not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_promo_product_id' => '(promo_id, product_id)',
            ],
            BDb::CONSTRAINTS => [
                'promo' => ['promo_id', $tPromo],
                'product' => ['product_id', $tProduct],
                'product_price' => ['product_price_id', $tProductPrice],
                'promo_product' => ['promo_product_id', $tPromoProduct, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);
        */

        $this->BDb->ddlTableDef($tProductPrice, [
            BDb::COLUMNS => [
                'promo_id' => 'int unsigned default null',
            ],
            BDb::CONSTRAINTS => [
                'promo' => ['promo_id', $tPromo],
            ]
        ]);
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $tCoupon = $this->Sellvana_Promo_Model_PromoCoupon->table();
        $tPromo = $this->Sellvana_Promo_Model_Promo->table();

        $this->BDb->ddlTableDef($tCoupon, [
            BDb::COLUMNS => [
                'id' => "INT(10) UNSIGNED NOT NULL AUTO_INCREMENT",
                'promo_id' => "INT(10) UNSIGNED NOT NULL",
                'code' => "VARCHAR(50) NOT NULL",
                'uses_per_customer' => "INT(10) UNSIGNED NULL ",
                'uses_total' => "INT(10) UNSIGNED NULL ",
                'total_used' => "INT(10) UNSIGNED NULL",
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                "promo" => ["promo_id", $tPromo],
            ]
        ]);
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();
        $tCart = $this->Sellvana_Sales_Model_Cart->table();
        $tOrder = $this->Sellvana_Sales_Model_Order->table();
        $tPromo = $this->Sellvana_Promo_Model_Promo->table();
        $tCoupon = $this->Sellvana_Promo_Model_PromoCoupon->table();
        $tPromoOrder = $this->Sellvana_Promo_Model_PromoOrder->table();
        $tPromoCart = $this->Sellvana_Promo_Model_PromoCart->table();

        $BDb = $this->BDb;
        $BDb->ddlTableDef($tCoupon, [
            BDb::COLUMNS => [
                'uses_per_customer' => BDb::DROP,
                'uses_total' => BDb::DROP,
            ]
        ]);

        $BDb->ddlTableDef($tPromo, [
            BDb::COLUMNS => [
                'description'     => BDb::DROP,
                'details'         => BDb::DROP,
                'manuf_vendor_id' => BDb::DROP,
                'buy_type'        => BDb::DROP,
                'buy_group'       => BDb::DROP,
                'buy_amount'      => BDb::DROP,
                'get_type'        => BDb::DROP,
                'get_group'       => BDb::DROP,
                'get_amount'      => BDb::DROP,
                'originator'      => BDb::DROP,
                'fulfillment'     => BDb::DROP,
                'coupon'          => BDb::DROP,
            ]
        ]);

        $BDb->ddlTableDef($tPromo, [
            BDb::COLUMNS => [
                'summary'                  => "VARCHAR(255) NOT NULL",
                'internal_notes'           => "TEXT NULL",
                'customer_label'           => "VARCHAR(255) NULL",
                'customer_details'         => "TEXT NULL",
                'promo_type'               => "ENUM('catalog','cart') NOT NULL DEFAULT 'cart'",
                'coupon_type'              => 'TINYINT UNSIGNED DEFAULT 0 COMMENT "0 = Do not use, 1 = Single code, 2 = multiple codes"',
                'coupon_uses_per_customer' => "INT(10) UNSIGNED NULL DEFAULT 0",
                'coupon_uses_total'        => "INT(10) UNSIGNED NULL DEFAULT 0",
                'data_serialized'          => "TEXT",
            ],
            BDb::KEYS => [
                'IDX_promo_status'     => "(status)",
                'IDX_promo_from_to_date' => "(from_date, to_date)",
            ]
        ]);


        $BDb->ddlTableDef($tPromoOrder, [
            BDb::COLUMNS => [
                'id'                 => "INT(10) unsigned not null auto_increment",
                'promo_id'           => "INT(10) UNSIGNED NOT NULL",
                'order_id'           => "INT(10) UNSIGNED NULL",
                'coupon_id'          => "INT(10) UNSIGNED NULL",
                'free_order_item_id' => "INT(10) UNSIGNED NULL",
                'coupon_code'        => "INT(10) UNSIGNED NULL",
                'subtotal_discount'  => "DECIMAL(12,2) NULL",
                'shipping_discount'  => "DECIMAL(12,2) NULL",
                'created_at'         => "DATETIME NOT NULL",
                'updated_at'         => "DATETIME NULL",
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                "promo" => ["promo_id", $tPromo],
                "order" => ["order_id", $tOrder],
                "coupon" => ["coupon_id", $tCoupon],
                "order_product" => ["free_order_item_id", $tProduct],
            ]
        ]);

        $BDb->ddlTableDef($tPromoCart, [
            BDb::COLUMNS => [
                'id'                 => "INT(10) unsigned not null auto_increment",
                'promo_id'           => "INT(10) UNSIGNED NOT NULL",
                'cart_id'            => "INT(10) UNSIGNED NOT NULL",
                'coupon_id'          => "INT(10) UNSIGNED NULL",
                'free_cart_item_id'  => "INT(10) UNSIGNED NULL",
                'coupon_code'        => "INT(10) UNSIGNED NULL",
                'subtotal_discount'  => "DECIMAL(12,2) NULL",
                'shipping_discount'  => "DECIMAL(12,2) NULL",
                'created_at'         => "DATETIME NOT NULL",
                'updated_at'         => "DATETIME NULL",
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                "promo" => ["promo_id", $tPromo],
                "cart" => ["cart_id", $tCart],
                "coupon" => ["coupon_id", $tCoupon],
                "cart_product" => ["free_cart_item_id", $tProduct],
            ]
        ]);
    }

    public function upgrade__0_1_8__0_1_9()
    {
        $tAdminUser = $this->FCom_Admin_Model_User->table();
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tPromo = $this->Sellvana_Promo_Model_Promo->table();
        $tPromoCoupon = $this->Sellvana_Promo_Model_PromoCoupon->table();
        $tPromoCart = $this->Sellvana_Promo_Model_PromoCart->table();
        $tPromoOrder = $this->Sellvana_Promo_Model_PromoOrder->table();
        $tCart = $this->Sellvana_Sales_Model_Cart->table();
        $tCartItem = $this->Sellvana_Sales_Model_Cart_Item->table();
        $tOrder = $this->Sellvana_Sales_Model_Order->table();
        $tOrderItem = $this->Sellvana_Sales_Model_Order_Item->table();
        $tPromoHistory = $this->Sellvana_Promo_Model_PromoHistory->table();

        $this->BDb->ddlTableDef($tPromo, [
            BDb::COLUMNS => [
                'site_ids' => 'varchar(255)',
                'customer_group_ids' => 'varchar(255)',
                'priority_order' => 'smallint not null default 0',
                'stop_flag' => 'tinyint not null default 0',
            ],
            BDb::KEYS => [
                'IDX_status_type_priority_date' => '(`status`, promo_type, coupon_type, priority_order, from_date, to_date)',
            ],
        ]);

        $this->BDb->ddlTableDef($tPromoCart, [
            BDb::COLUMNS => [
                'customer_id' => 'int unsigned',
                'data_serialized' => 'text',
                'created_at' => 'RENAME create_at datetime not null',
                'updated_at' => BDb::DROP,#'RENAME update_at datetime',
                'coupon_code' => 'varchar(50)',
            ],
            BDb::CONSTRAINTS => [
                'cart_product' => BDb::DROP,
                'cart_item' => ['free_cart_item_id', $tCartItem],
                'customer' => ['customer_id', $tCustomer, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);

        $this->BDb->ddlTableDef($tPromoOrder, [
            BDb::COLUMNS => [
                'customer_id' => 'int unsigned',
                'data_serialized' => 'text',
                'created_at' => 'RENAME create_at datetime not null',
                'updated_at' => 'RENAME update_at datetime',
                'coupon_code' => 'varchar(50)',
            ],
            BDb::CONSTRAINTS => [
                'order_product' => BDb::DROP,
                'order_item' => ['free_order_item_id', $tOrderItem],
                'customer' => ['customer_id', $tCustomer, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);

        $this->BDb->ddlTableDef($tPromoHistory, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'promo_id' => 'int unsigned not null',
                'action_type' => 'varchar(20) not null',
                'coupon_id' => 'int unsigned',
                'coupon_code' => 'varchar(50)',
                'admin_user_id' => 'int unsigned',
                'customer_id' => 'int unsigned',
                'cart_id' => 'int unsigned',
                'order_id' => 'int unsigned',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime',
                'description' => 'text',
                'data_serialized' => 'text',
                'amount' => 'decimal(12,2)',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'promo' => ['promo_id', $tPromo],
                'coupon' => ['coupon_id', $tPromoCoupon],
                'admin_user' => ['admin_user_id', $tAdminUser],
                'customer' => ['customer_id', $tCustomer],
                'cart' => ['cart_id', $tCart],
                'order' => ['order_id', $tOrder],
            ],
        ]);
    }

    public function upgrade__0_1_9__0_1_10()
    {
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();
        $tPromo = $this->Sellvana_Promo_Model_Promo->table();
        $tPromoProduct = $this->Sellvana_Promo_Model_PromoProduct->table();

        $this->BDb->ddlTableDef($tPromoProduct, [
            BDb::COLUMNS => [
                'group_id' => BDb::DROP,
                'calc_status' => 'tinyint not null default 0',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::KEYS => [
                'IDX_calc_status' => '(calc_status)',
            ],
            BDb::CONSTRAINTS => [
                'promo' => ['promo_id', $tPromo],
                'product' => ['product_id', $tProduct],
            ],
        ]);

        $this->BDb->ddlTableDef($tPromo, [
            BDb::COLUMNS => [
                'limit_per_coupon'         => "INT(10) UNSIGNED NULL DEFAULT 1",
                'coupon_uses_per_customer' => "RENAME limit_per_customer INT(10) UNSIGNED NULL DEFAULT 0",
                'coupon_uses_total'        => "RENAME limit_per_promo INT(10) UNSIGNED NULL DEFAULT 0",
            ]
        ]);
    }

    public function upgrade__0_1_10__0_1_11()
    {
        //todo
        $tPromoDisplay = $this->Sellvana_Promo_Model_PromoDisplay->table();
        $tPromo = $this->Sellvana_Promo_Model_Promo->table();
        $this->BDb->ddlTableDef($tPromoDisplay, [
            BDb::COLUMNS     => [
                'id'              => 'int unsigned not null auto_increment',
                'promo_id'        => 'int unsigned not null',
                'page_type'       => 'varchar(50) not null default "home_page"',
                'page_location'   => 'varchar(50) not null default ""',
                'content_type'    => 'varchar(20) not null default "html"',
                'data_serialized' => 'text',
                'create_at'       => 'datetime not null',
                'update_at'       => 'datetime'
            ],
            BDb::PRIMARY     => '(id)',
            BDb::CONSTRAINTS => [
                'promo' => ['promo_id', $tPromo],
            ]
        ]);

        $this->BDb->ddlTableDef($tPromo, [
            BDb::COLUMNS => [
                'display_on_central_page' => 'bool not null default 0'
            ]
        ]);
    }

    public function upgrade__0_1_11__0_1_12()
    {
        $tPromo = $this->Sellvana_Promo_Model_Promo->table();
        $tCart = $this->Sellvana_Sales_Model_Cart->table();
        $tCartItem = $this->Sellvana_Sales_Model_Cart_Item->table();

        $tPromoCart = $this->Sellvana_Promo_Model_PromoCart->table();
        $tPromoCartItem = $this->Sellvana_Promo_Model_PromoCartItem->table();

        $this->BDb->ddlTableDef($tPromoCart, [
            BDb::COLUMNS => [
                'shipping_free' => 'tinyint not null default 0',
            ],
        ]);

        $this->BDb->ddlTableDef($tPromoCartItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'promo_cart_id' => 'int unsigned not null',
                'promo_id' => 'int unsigned not null',
                'cart_id' => 'int unsigned not null',
                'cart_item_id' => 'int unsigned not null',
                'item_type' => 'tinyint not null default 0',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_promo_cart_item' => '(promo_id, cart_id, cart_item_id, item_type)',
            ],
            BDb::CONSTRAINTS => [
                'promo_cart' => ['promo_cart_id', $tPromoCart],
                'promo' => ['promo_id', $tPromo],
                'cart' => ['cart_id', $tCart],
                'cart_item' => ['cart_item_id', $tCartItem],
            ],
        ]);
    }

    public function upgrade__0_1_12__0_1_13()
    {
        $tPromo = $this->Sellvana_Promo_Model_Promo->table();

        $this->BDb->ddlTableDef($tPromo, [
            BDb::COLUMNS => [
                'display_on_central_page' => 'RENAME display_index tinyint not null default 0',
                'display_index_order' => 'int unsigned not null default 0',
                'display_index_section' => "varchar(10) not null default 'regular'",
                'display_index_type' => "varchar(10) not null default 'text'",
                'display_index_showexp' => 'tinyint unsigned not null default 1',
            ],
            BDb::KEYS => [
                'IDX_display_index' => '(display_index, display_index_order)',
            ],
        ]);
    }

    public function upgrade__0_1_13__0_1_14()
    {
        $tPromo = $this->Sellvana_Promo_Model_Promo->table();

        $this->BDb->ddlTableDef($tPromo, [
            BDb::COLUMNS => [
                'conditions_operator' => "varchar(6) not null default 'always'",
            ]
        ]);
    }

    public function upgrade__0_2_0__0_2_1()
    {
        $tPromoProduct = $this->Sellvana_Promo_Model_PromoProduct->table();

        $this->BDb->ddlTableDef($tPromoProduct, [
            BDb::COLUMNS => [
                'sort_order' => 'smallint not null default 0',
                'action_amount' => 'decimal(12,2) not null default 0',
                'action_op' => "char(2) not null default '=$'",
            ],
            BDb::KEYS => [
                'IDX_product_calc_sort' => '(product_id, calc_status, sort_order)',
            ],
        ]);

        /*
        $tProduct           = $this->Sellvana_Catalog_Model_Product->table();
        $tProductPrice      = $this->Sellvana_Catalog_Model_ProductPrice->table();
        $tPromo             = $this->Sellvana_Promo_Model_Promo->table();
        $tPromoProductPrice = $this->Sellvana_Promo_Model_PromoProductPrice->table();

        $this->BDb->ddlTableDef($tPromoProductPrice, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'promo_id' => 'int unsigned not null',
                'product_id' => 'int unsigned not null',
                'product_price_id' => 'int unsigned not null',
                'promo_product_id' => 'int unsigned null',
                'sort_order' => 'smallint not null default 0',
                'stop_flag' => 'tinyint not null default 0',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_sort_promo_product' => '(sort_order, promo_id, product_id)',
            ],
            BDb::CONSTRAINTS => [
                'promo' => ['promo_id', $tPromo],
                'product' => ['product_id', $tProduct],
                'product_price' => ['product_price_id', $tProductPrice],
                'promo_product' => ['promo_product_id', $tPromoProduct, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);
        */
    }


    public function upgrade__0_2_1__0_2_2()
    {
        $tProductPrice = $this->Sellvana_Catalog_Model_ProductPrice->table();
        $tPromo = $this->Sellvana_Promo_Model_Promo->table();

        $this->BDb->ddlTableDef($tProductPrice, [
            BDb::COLUMNS => [
                'promo_id' => 'int unsigned default null',
            ],
            BDb::CONSTRAINTS => [
                'promo' => ['promo_id', $tPromo],
            ]
        ]);
    }
}
/*
 * Text (Html)
 CMS Block
 Text (Markdown)
 */
