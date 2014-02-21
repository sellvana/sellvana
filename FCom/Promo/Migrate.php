<?php

class FCom_Promo_Migrate extends BClass
{
    public function install__0_1_0()
    {
        $tPromo = FCom_Promo_Model_Promo::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tPromo}(
    `id` INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT ,
    `description` VARCHAR(255) COLLATE utf8_general_ci NOT NULL  ,
    `details` TEXT COLLATE utf8_general_ci NULL  ,
    `manuf_vendor_id` INT(10) UNSIGNED NOT NULL  ,
    `from_date` DATE NULL  ,
    `to_date` DATE NULL  ,
    `status` ENUM('template','pending','active','expired') COLLATE utf8_general_ci NOT NULL  DEFAULT 'pending' ,
    `buy_type` ENUM('qty','$') COLLATE utf8_general_ci NOT NULL  DEFAULT 'qty' ,
    `buy_group` ENUM('one','any','all') COLLATE utf8_general_ci NOT NULL  DEFAULT 'one' ,
    `buy_amount` INT(11) NULL  ,
    `get_type` ENUM('qty','$','%','text','choice') COLLATE utf8_general_ci NOT NULL  DEFAULT 'qty' ,
    `get_group` ENUM('same_prod','same_group','any_group','diff_group') COLLATE utf8_general_ci NOT NULL  DEFAULT 'same_prod' ,
    `get_amount` INT(11) NULL  ,
    `originator` ENUM('manuf','vendor') COLLATE utf8_general_ci NOT NULL  DEFAULT 'manuf' ,
    `fulfillment` ENUM('manuf','vendor') COLLATE utf8_general_ci NOT NULL  DEFAULT 'manuf' ,
    `create_dt` DATETIME NOT NULL  ,
    `update_dt` DATETIME NULL  ,
    PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET='utf8';
        ");

        $tGroup = FCom_Promo_Model_Group::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tGroup}(
    `id` INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT ,
    `promo_id` INT(10) UNSIGNED NOT NULL  ,
    `group_type` ENUM('buy','get') COLLATE utf8_general_ci NOT NULL  ,
    `group_name` VARCHAR(255) COLLATE utf8_general_ci NOT NULL  ,
    PRIMARY KEY (`id`) ,
    KEY `FK_promo_group_promo`(`promo_id`)
) ENGINE=INNODB DEFAULT CHARSET='utf8';
        ");

        $tMedia = FCom_Promo_Model_Media::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS $tMedia(
    `id` INT(10) UNSIGNED NOT NULL  AUTO_INCREMENT ,
    `promo_id` INT(10) UNSIGNED NULL  ,
    `file_id` INT(11) UNSIGNED NOT NULL  ,
    `manuf_vendor_id` INT(11) UNSIGNED NULL  ,
    `promo_status` CHAR(1) COLLATE utf8_general_ci NOT NULL  DEFAULT 'A' ,
    PRIMARY KEY (`id`) ,
    KEY `FK_promo_media_file`(`file_id`) ,
    KEY `FK_promo_media_promo`(`promo_id`)
) ENGINE=INNODB DEFAULT CHARSET='utf8';
        ");

        $tProduct = FCom_Promo_Model_Product::table();
        BDb::run("
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


    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tCart = FCom_Promo_Model_Cart::table();
        BDb::run("
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
        $tCart = FCom_Promo_Model_Cart::table();
        BDb::ddlAddColumns($tCart, array('updated_dt' => "datetime"));
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $table = FCom_Promo_Model_Cart::table();
        BDb::ddlTableDef($table, array(
            'COLUMNS' => array(
                  'updated_dt'      => 'RENAME updated_at datetime NULL',
            ),
        ));
        $table = FCom_Promo_Model_Promo::table();
        BDb::ddlTableDef($table, array(
            'COLUMNS' => array(
                  'create_dt'      => 'RENAME create_at datetime NOT NULL',
                  'update_dt'      => 'RENAME update_at datetime NULL',
            ),
        ));
    }

    public function upgrade__0_1_3__0_1_4()
    {

        $table = FCom_Promo_Model_Cart::table();
        BDb::ddlTableDef($table, array(
            'COLUMNS' => array(
                  'updated_at'      => 'RENAME update_at datetime NULL',
            ),
        ));
    }

    public function upgrade__0_1_4__0_1_5()
    {

        $table = FCom_Promo_Model_Promo::table();
        BDb::ddlTableDef($table, array(
            'COLUMNS' => array(
                "coupon"          => "varchar(100)",
                "manuf_vendor_id" => "INT(10) UNSIGNED NULL",
                "buy_group"       => "ENUM('one', 'any', 'all', 'cat', 'anyp') COLLATE utf8_general_ci NOT NULL  DEFAULT 'one'"
            ),
        ));
    }

    public function upgrade__0_1_5__0_1_6()
    {

        $table = FCom_Promo_Model_Promo::table();
        BDb::ddlTableDef($table, array(
            'COLUMNS' => array(
                "get_type"       => "enum('qty','$','%','text','choice','free') NOT NULL DEFAULT 'qty'"
            ),
        ));
    }
}
