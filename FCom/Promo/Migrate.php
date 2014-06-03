<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
        $this->BDb->ddlTableDef($tCart, ['COLUMNS' => ['updated_dt' => "datetime"]]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $table = $this->FCom_Promo_Model_Cart->table();
        $this->BDb->ddlTableDef($table, [
            'COLUMNS' => [
                  'updated_dt'      => 'RENAME updated_at datetime NULL',
            ],
        ]);
        $table = $this->FCom_Promo_Model_Promo->table();
        $this->BDb->ddlTableDef($table, [
            'COLUMNS' => [
                  'create_dt'      => 'RENAME create_at datetime NOT NULL',
                  'update_dt'      => 'RENAME update_at datetime NULL',
            ],
        ]);
    }

    public function upgrade__0_1_3__0_1_4()
    {

        $table = $this->FCom_Promo_Model_Cart->table();
        $this->BDb->ddlTableDef($table, [
            'COLUMNS' => [
                  'updated_at'      => 'RENAME update_at datetime NULL',
            ],
        ]);
    }

    public function upgrade__0_1_4__0_1_5()
    {

        $table = $this->FCom_Promo_Model_Promo->table();
        $this->BDb->ddlTableDef($table, [
            'COLUMNS' => [
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
            'COLUMNS' => [
                "get_type"       => "enum('qty','$','%','text','choice','free') NOT NULL DEFAULT 'qty'"
            ],
        ]);
    }
}
