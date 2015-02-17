<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @property FCom_Promo_Model_PromoCoupon    $FCom_Promo_Model_PromoCoupon
 * @property FCom_Promo_Model_Promo          $FCom_Promo_Model_Promo
 * @property FCom_Promo_Model_PromoHistory   $FCom_Promo_Model_PromoHistory
 * @property FCom_Promo_Model_Group          $FCom_Promo_Model_Group
 * @property FCom_Promo_Model_PromoMedia     $FCom_Promo_Model_PromoMedia
 * @property FCom_Promo_Model_PromoProduct   $FCom_Promo_Model_PromoProduct
 * @property FCom_Promo_Model_PromoCart      $FCom_Promo_Model_PromoCart
 * @property FCom_Customer_Model_Customer    $FCom_Customer_Model_Customer
 * @property FCom_Promo_Model_PromoOrder     $FCom_Promo_Model_PromoOrder
 * @property FCom_Sales_Model_Cart           $FCom_Sales_Model_Cart
 * @property FCom_Sales_Model_Cart_Item      $FCom_Sales_Model_Cart_Item
 * @property FCom_Sales_Model_Order          $FCom_Sales_Model_Order
 * @property FCom_Sales_Model_Order_Item     $FCom_Sales_Model_Order_Item
 * @property FCom_Catalog_Model_Product      $FCom_Catalog_Model_Product
 * @property FCom_Admin_Model_User           $FCom_Admin_Model_User
 * @property FCom_Promo_Model_PromoDisplay   $FCom_Promo_Model_PromoDisplay
 */
class FCom_Promo_Migrate extends BClass
{
    public function install__0_1_6()
    {
        $tPromo = $this->FCom_Promo_Model_Promo->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tPromo}(
            `id` INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT ,
            `description` VARCHAR(255)  NOT NULL  ,
            `details` TEXT  NULL  ,
            `manuf_vendor_id` INT(10) UNSIGNED NULL  ,
            `from_date` DATE NULL  ,
            `to_date` DATE NULL  ,
            `status` ENUM('template','pending','active','expired')  NOT NULL  DEFAULT 'pending' ,
            `buy_type` ENUM('qty','$')  NOT NULL  DEFAULT 'qty' ,
            `buy_group` ENUM('one', 'any', 'all', 'cat', 'anyp')  NOT NULL  DEFAULT 'one',
            `buy_amount` INT(11) NULL  ,
            `get_type` ENUM('qty','$','%','text','choice','free')  NOT NULL  DEFAULT 'qty' ,
            `get_group` ENUM('same_prod','same_group','any_group','diff_group')  NOT NULL  DEFAULT 'same_prod' ,
            `get_amount` INT(11) NULL  ,
            `originator` ENUM('manuf','vendor')  NOT NULL  DEFAULT 'manuf' ,
            `fulfillment` ENUM('manuf','vendor')  NOT NULL  DEFAULT 'manuf' ,
            `create_at` DATETIME NOT NULL  ,
            `update_at` DATETIME NULL  ,
            `coupon` varchar(100) NULL  ,
            PRIMARY KEY (`id`)
            ) ENGINE=INNODB DEFAULT CHARSET='utf8';
        ");

        $tGroup = $this->FCom_Promo_Model_Group->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tGroup}(
    `id` INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT ,
    `promo_id` INT(10) UNSIGNED NOT NULL  ,
    `group_type` ENUM('buy','get')  NOT NULL  ,
    `group_name` VARCHAR(255)  NOT NULL  ,
    PRIMARY KEY (`id`) ,
    KEY `FK_promo_group_promo`(`promo_id`)
) ENGINE=INNODB DEFAULT CHARSET='utf8';
        ");

        $tMedia = $this->FCom_Promo_Model_PromoMedia->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS $tMedia(
    `id` INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT ,
    `promo_id` INT(10) UNSIGNED NULL  ,
    `file_id` INT(11) UNSIGNED NOT NULL  ,
    `manuf_vendor_id` INT(11) UNSIGNED NULL  ,
    `promo_status` CHAR(1)  NOT NULL  DEFAULT 'A' ,
    PRIMARY KEY (`id`) ,
    KEY `FK_promo_media_file`(`file_id`) ,
    KEY `FK_promo_media_promo`(`promo_id`)
) ENGINE=INNODB DEFAULT CHARSET='utf8';
        ");

        $tProduct = $this->FCom_Promo_Model_PromoProduct->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS $tProduct(
    `id` INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT ,
    `promo_id` INT(10) UNSIGNED NOT NULL  ,
    `group_id` INT(10) UNSIGNED NOT NULL  ,
    `product_id` INT(11) UNSIGNED NOT NULL  ,
    `qty` TINYINT(3) UNSIGNED NULL  ,
    PRIMARY KEY (`id`) ,
    KEY `FK_promo_product_promo`(`promo_id`) ,
    KEY `FK_promo_product_product`(`product_id`) ,
    KEY `FK_promo_product_group`(`group_id`)
) ENGINE=INNODB DEFAULT CHARSET='utf8';
        ");

        $tCart = $this->FCom_Promo_Model_PromoCart->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS $tCart(
            `id` INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT ,
            `cart_id` INT(10) UNSIGNED NOT NULL  ,
            `promo_id` INT(10) UNSIGNED NOT NULL  ,
            `update_at` datetime NULL  ,
            PRIMARY KEY (`id`)
        ) ENGINE=INNODB DEFAULT CHARSET='utf8';
        ");
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tCart = $this->FCom_Promo_Model_PromoCart->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS $tCart(
            `id` INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT ,
            `cart_id` INT(10) UNSIGNED NOT NULL  ,
            `promo_id` INT(10) UNSIGNED NOT NULL  ,
            PRIMARY KEY (`id`)
        ) ENGINE=INNODB DEFAULT CHARSET='utf8';
        ");
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tCart = $this->FCom_Promo_Model_PromoCart->table();
        $this->BDb->ddlTableDef($tCart, [BDb::COLUMNS => ['updated_dt' => "datetime"]]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $table = $this->FCom_Promo_Model_PromoCart->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'updated_dt' => 'RENAME updated_at datetime NULL',
            ],
        ]);
        $table = $this->FCom_Promo_Model_Promo->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'create_dt' => 'RENAME create_at datetime NOT NULL',
                'update_dt' => 'RENAME update_at datetime NULL',
            ],
        ]);
    }

    public function upgrade__0_1_3__0_1_4()
    {

        $table = $this->FCom_Promo_Model_PromoCart->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'updated_at' => 'RENAME update_at datetime NULL',
            ],
        ]);
    }

    public function upgrade__0_1_4__0_1_5()
    {

        $table = $this->FCom_Promo_Model_Promo->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                "coupon"          => "varchar(100)",
                "manuf_vendor_id" => "INT(10) UNSIGNED NULL",
                "buy_group"       => "ENUM('one', 'any', 'all', 'cat', 'anyp')  NOT NULL  DEFAULT 'one'"
            ],
        ]);
    }

    public function upgrade__0_1_5__0_1_6()
    {

        $table = $this->FCom_Promo_Model_Promo->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                "get_type" => "enum('qty','$','%','text','choice','free') NOT NULL DEFAULT 'qty'"
            ],
        ]);
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $tCoupon = $this->FCom_Promo_Model_PromoCoupon->table();
        $tPromo = $this->FCom_Promo_Model_Promo->table();

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
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $tCart = $this->FCom_Sales_Model_Cart->table();
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $tPromo = $this->FCom_Promo_Model_Promo->table();
        $tCoupon = $this->FCom_Promo_Model_PromoCoupon->table();
        $tPromoOrder = $this->FCom_Promo_Model_PromoOrder->table();
        $tPromoCart = $this->FCom_Promo_Model_PromoCart->table();

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
        $tCustomer = $this->FCom_Customer_Model_Customer->table();
        $tPromo = $this->FCom_Promo_Model_Promo->table();
        $tPromoCoupon = $this->FCom_Promo_Model_PromoCoupon->table();
        $tPromoCart = $this->FCom_Promo_Model_PromoCart->table();
        $tPromoOrder = $this->FCom_Promo_Model_PromoOrder->table();
        $tCart = $this->FCom_Sales_Model_Cart->table();
        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
        $tOrder = $this->FCom_Sales_Model_Order->table();
        $tOrderItem = $this->FCom_Sales_Model_Order_Item->table();
        $tPromoHistory = $this->FCom_Promo_Model_PromoHistory->table();

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
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $tPromo = $this->FCom_Promo_Model_Promo->table();
        $tPromoProduct = $this->FCom_Promo_Model_PromoProduct->table();

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
        $tPromoDisplay = $this->FCom_Promo_Model_PromoDisplay->table();
        $tPromo = $this->FCom_Promo_Model_Promo->table();
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
}
/*
 * Text (Html)
 CMS Block
 Text (Markdown)
 */
