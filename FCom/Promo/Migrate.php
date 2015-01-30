<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @property FCom_Promo_Model_Coupon         $FCom_Promo_Model_Coupon
 * @property FCom_Promo_Model_Promo          $FCom_Promo_Model_Promo
 * @property FCom_Promo_Model_Group          $FCom_Promo_Model_Group
 * @property FCom_Promo_Model_Media          $FCom_Promo_Model_Media
 * @property FCom_Promo_Model_Product        $FCom_Promo_Model_Product
 * @property FCom_Promo_Model_Cart           $FCom_Promo_Model_Cart
 * @property FCom_Promo_Model_CustomerCoupon $FCom_Promo_Model_CustomerCoupon
 * @property FCom_Customer_Model_Customer    $FCom_Customer_Model_Customer
 * @property FCom_Promo_Model_Order          $FCom_Promo_Model_Order
 * @property FCom_Sales_Model_Cart           $FCom_Sales_Model_Cart
 * @property FCom_Sales_Model_Order          $FCom_Sales_Model_Order
 * @property FCom_Catalog_Model_Product      $FCom_Catalog_Model_Product
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

        $tMedia = $this->FCom_Promo_Model_Media->table();
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

        $tProduct = $this->FCom_Promo_Model_Product->table();
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

        $tCart = $this->FCom_Promo_Model_Cart->table();
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
        $tCart = $this->FCom_Promo_Model_Cart->table();
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
        $tCart = $this->FCom_Promo_Model_Cart->table();
        $this->BDb->ddlTableDef($tCart, [BDb::COLUMNS => ['updated_dt' => "datetime"]]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $table = $this->FCom_Promo_Model_Cart->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'updated_dt'      => 'RENAME updated_at datetime NULL',
            ],
        ]);
        $table = $this->FCom_Promo_Model_Promo->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'create_dt'      => 'RENAME create_at datetime NOT NULL',
                  'update_dt'      => 'RENAME update_at datetime NULL',
            ],
        ]);
    }

    public function upgrade__0_1_3__0_1_4()
    {

        $table = $this->FCom_Promo_Model_Cart->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'updated_at'      => 'RENAME update_at datetime NULL',
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
                "get_type"       => "enum('qty','$','%','text','choice','free') NOT NULL DEFAULT 'qty'"
            ],
        ]);
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $table = $this->FCom_Promo_Model_Coupon->table();

        $this->BDb->ddlTableDef($table, [
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
                "FK_{$table}_promo" => ["promo_id", $this->FCom_Promo_Model_Promo->table()]
            ]
        ]);
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $tCoupon = $this->FCom_Promo_Model_Coupon->table();
        $BDb = $this->BDb;
        $BDb->ddlTableDef($tCoupon, [
            BDb::COLUMNS => [
                'uses_per_customer' => BDb::DROP,
                'uses_total' => BDb::DROP,
            ]
        ]);

        $tPromo = $this->FCom_Promo_Model_Promo->table();
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

        $tPromoOrder = $this->FCom_Promo_Model_Order->table();
        $tPromoCart = $this->FCom_Promo_Model_Cart->table();

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
                "FK_{$tPromoOrder}_promo" => ["promo_id", $this->FCom_Promo_Model_Promo->table()],
                "FK_{$tPromoOrder}_order" => ["order_id", $this->FCom_Sales_Model_Order->table()],
                "FK_{$tPromoOrder}_coupon" => ["coupon_id", $this->FCom_Promo_Model_Coupon->table()],
                "FK_{$tPromoOrder}_order_product" => ["free_order_item_id", $this->FCom_Catalog_Model_Product->table()],
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
                "FK_{$tPromoCart}_promo" => ["promo_id", $this->FCom_Promo_Model_Promo->table()],
                "FK_{$tPromoCart}_cart" => ["cart_id", $this->FCom_Sales_Model_Cart->table()],
                "FK_{$tPromoCart}_coupon" => ["coupon_id", $this->FCom_Promo_Model_Coupon->table()],
                "FK_{$tPromoCart}_cart_product" => ["free_cart_item_id", $this->FCom_Catalog_Model_Product->table()],
            ]
        ]);
    }

}
