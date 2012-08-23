<?php

class FCom_ProductReviews_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.1', array($this, 'install'));
        BMigrate::upgrade('0.1.1', '0.1.2', array($this, 'upgrade_0_1_2'));
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
ALTER TABLE {$tProduct} ADD COLUMN avg_rating decimal(5,2) NULL;
ALTER TABLE {$tProduct} ADD COLUMN num_reviews int NULL;
        ");
    }
}