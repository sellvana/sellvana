<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_ProductReviews_Migrate
 *
 * @property FCom_ProductReviews_Model_ReviewFlag $FCom_ProductReviews_Model_ReviewFlag
 * @property FCom_ProductReviews_Model_Review $FCom_ProductReviews_Model_Review
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 */
class FCom_ProductReviews_Migrate extends BClass
{
    public function upgrade__0_1_5__0_2_0()
    {
        $tReviewFlag = $this->FCom_ProductReviews_Model_ReviewFlag->table();
        $this->BDb->run("CREATE TABLE IF NOT EXISTS {$tReviewFlag}  (
            `review_id` INT UNSIGNED NOT NULL ,
            `customer_id` INT UNSIGNED NOT NULL,
            `helpful` tinyint(1) NOT NULL default 0,
            `offensive` tinyint(1) NOT NULL default 0,
            UNIQUE KEY `rev2cust` (`review_id`,`customer_id`)
            ) ENGINE = InnoDB;"
        );
    }

    public function install__0_2_5()
    {
        $tReview = $this->FCom_ProductReviews_Model_Review->table();
        $tReviewFlag = $this->FCom_ProductReviews_Model_ReviewFlag->table();
        $tProduct = $this->FCom_Catalog_Model_Product->table();

        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tReview} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `product_id` int(10) unsigned NOT NULL,
            `customer_id` int(10) unsigned NOT NULL,
            `rating` tinyint(1) unsigned not null,
            `rating1` tinyint(1) unsigned NOT NULL,
            `rating2` tinyint(1) unsigned NOT NULL,
            `rating3` tinyint(1) unsigned NOT NULL,
            `approved` int(11) not null default 0,
            `helpful` int(11) not null DEFAULT '0',
            `helpful_voices` bigint(11) not null DEFAULT '0',
            `offensive` int(11) null,
            `title` varchar(255) NOT NULL,
            `create_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `text` text,
            PRIMARY KEY (`id`),
            KEY `IDX_product_approved` (`product_id`,`approved`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->BDb->ddlTableDef($tProduct, [
            BDb::COLUMNS => [
                'avg_rating' => 'decimal(5,2) null',
                'num_reviews' => 'int null',
            ],
        ]);

        $this->BDb->run("CREATE TABLE IF NOT EXISTS {$tReviewFlag}  (
            `id` int unsigned not null auto_increment primary key,
            `review_id` INT UNSIGNED NOT NULL ,
            `customer_id` INT UNSIGNED NOT NULL,
            `helpful` tinyint(1) NOT NULL default 0,
            `offensive` tinyint(1) NOT NULL default 0,
            UNIQUE KEY `rev2cust` (`review_id`,`customer_id`)
            ) ENGINE = InnoDB;"
        );

        $hlp = $this->FCom_CatalogIndex_Model_Field;
        if (!$hlp->load('avg_rating', 'field_name')) {
            $hlp->create([
                'field_name' => 'avg_rating',
                'field_label' => 'Average Rating',
                'field_type' => 'varchar',
                'weight' => 0,
                'source_type' => 'callback',
                'source_callback' => 'FCom_ProductReviews_Model_Review::indexAvgRating',
                'filter_type' => 'exclusive',
                'filter_counts' => 1,
                'filter_order' => 10,
            ])->save();
        }
    }

    public function upgrade__0_2_0__0_2_1()
    {
        $table = $this->FCom_ProductReviews_Model_Review->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'created_dt'  => 'RENAME created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],
        ]);
    }

    public function upgrade__0_2_1__0_2_2()
    {
        $table = $this->FCom_ProductReviews_Model_Review->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'created_at'  => 'RENAME create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],
        ]);
    }

    public function upgrade__0_2_2__0_2_3()
    {
        $table = $this->FCom_ProductReviews_Model_Review->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'rating1' => 'tinyint(1) unsigned not null after rating',
                'rating2' => 'tinyint(1) unsigned not null after rating1',
                'rating3' => 'tinyint(1) unsigned not null after rating2',
            ],
        ]);
    }

    public function upgrade__0_2_3__0_2_4()
    {
        $table = $this->FCom_ProductReviews_Model_Review->table();
        $this->BDb->ddlTableDef($table, [
            BDb::KEYS => [
                'IDX_product_approved' => '(product_id, approved)',
            ],
        ]);
    }

    public function upgrade__0_2_4__0_2_5()
    {
        $hlp = $this->FCom_CatalogIndex_Model_Field;
        if (!$hlp->load('avg_rating', 'field_name')) {
            $hlp->create([
                'field_name' => 'avg_rating',
                'field_label' => 'Average Rating',
                'field_type' => 'varchar',
                'weight' => 0,
                'source_type' => 'callback',
                'source_callback' => 'FCom_ProductReviews_Model_Review::indexAvgRating',
                'filter_type' => 'exclusive',
                'filter_counts' => 1,
                'filter_order' => 10,
            ])->save();
        }
    }
}
