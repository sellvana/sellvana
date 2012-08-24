<?php

class FCom_ProductReviews_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.1', array($this, 'install'));
        BMigrate::upgrade('0.1.1', '0.1.2', array($this, 'upgrade_0_1_2'));
        BMigrate::upgrade('0.1.2', '0.1.3', array($this, 'upgrade_0_1_3'));
        BMigrate::upgrade('0.1.3', '0.1.4', array($this, 'upgrade_0_1_4'));
        BMigrate::upgrade('0.1.4', '0.1.5', array($this, 'upgrade_0_1_5'));
    }

    public function install()
    {
        $tReviews = FCom_ProductReviews_Model_Reviews::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tReviews} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `product_id` int(10) unsigned NOT NULL,
            `customer_id` int(10) unsigned NOT NULL,
            `rating` tinyint(1) unsigned not null,
            `helpful` int(11) not null DEFAULT '0',
            `helpful_voices` bigint(11) not null DEFAULT '0',
            `title` varchar(255) NOT NULL,
            `created_dt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `text` text,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function upgrade_0_1_2()
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::run("
            ALTER TABLE {$tProduct} ADD COLUMN avg_rating decimal(5,2) NULL,
            ADD COLUMN num_reviews int NULL;
        ");
    }
    public function upgrade_0_1_3()
    {
        $tReviews = FCom_ProductReviews_Model_Reviews::table();
        if (false == BDb::ddlFieldInfo($tReviews, 'offensive') ) {
            BDb::run("
                ALTER TABLE {$tReviews} ADD COLUMN offensive int(11) NULL;
            ");
        }
    }

    public function upgrade_0_1_4()
    {
        $tReviewsHelpful2Customer = FCom_ProductReviews_Model_Helpful2Customer::table();
        BDb::run("CREATE TABLE IF NOT EXISTS {$tReviewsHelpful2Customer}  (
            `review_id` INT UNSIGNED NOT NULL ,
            `customer_id` INT UNSIGNED NOT NULL,
            `mark` tinyint(1) NOT NULL default 0,
            UNIQUE KEY `rev2cust` (`review_id`,`customer_id`)
            ) ENGINE = InnoDB;"
        );

        $tReviewsOffensive2Customer = FCom_ProductReviews_Model_Offensive2Customer::table();
        BDb::run("CREATE TABLE IF NOT EXISTS {$tReviewsOffensive2Customer} (
            `review_id` INT UNSIGNED NOT NULL ,
            `customer_id` INT UNSIGNED NOT NULL,
            `offensive` tinyint(1) NOT NULL default 0,
            UNIQUE KEY `rev2cust` (`review_id`,`customer_id`)
            ) ENGINE = InnoDB;"
        );
    }

    public function upgrade_0_1_5()
    {
        $tReviews = FCom_ProductReviews_Model_Reviews::table();
        if (false == BDb::ddlFieldInfo($tReviews, 'approved') ) {
            BDb::run("
                ALTER TABLE {$tReviews} ADD COLUMN approved int(11) NOT NULL default 0;
            ");
        }
    }
}