<?php

/**
 * Class Sellvana_ProductReviews_Migrate
 *
 * @property Sellvana_ProductReviews_Model_ReviewFlag $Sellvana_ProductReviews_Model_ReviewFlag
 * @property Sellvana_ProductReviews_Model_Review $Sellvana_ProductReviews_Model_Review
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_CatalogIndex_Model_Field $Sellvana_CatalogIndex_Model_Field
 */
class Sellvana_ProductReviews_Migrate extends BClass
{
    public function upgrade__0_1_5__0_2_0()
    {
        $tReviewFlag = $this->Sellvana_ProductReviews_Model_ReviewFlag->table();
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
        $tReview = $this->Sellvana_ProductReviews_Model_Review->table();
        $tReviewFlag = $this->Sellvana_ProductReviews_Model_ReviewFlag->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();

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

        $hlp = $this->Sellvana_CatalogIndex_Model_Field;
        if (!$hlp->load('avg_rating', 'field_name')) {
            $hlp->create([
                'field_name' => 'avg_rating',
                'field_label' => 'Average Rating',
                'field_type' => 'varchar',
                'weight' => 0,
                'source_type' => 'callback',
                'source_callback' => 'Sellvana_ProductReviews_Model_Review::indexAvgRating',
                'filter_type' => 'exclusive',
                'filter_counts' => 1,
                'filter_order' => 10,
            ])->save();
        }
    }

    public function upgrade__0_2_0__0_2_1()
    {
        $table = $this->Sellvana_ProductReviews_Model_Review->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'created_dt'  => 'RENAME created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],
        ]);
    }

    public function upgrade__0_2_1__0_2_2()
    {
        $table = $this->Sellvana_ProductReviews_Model_Review->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'created_at'  => 'RENAME create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],
        ]);
    }

    public function upgrade__0_2_2__0_2_3()
    {
        $table = $this->Sellvana_ProductReviews_Model_Review->table();
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
        $table = $this->Sellvana_ProductReviews_Model_Review->table();
        $this->BDb->ddlTableDef($table, [
            BDb::KEYS => [
                'IDX_product_approved' => '(product_id, approved)',
            ],
        ]);
    }

    public function upgrade__0_2_4__0_2_5()
    {
        $hlp = $this->Sellvana_CatalogIndex_Model_Field;
        if (!$hlp->load('avg_rating', 'field_name')) {
            $hlp->create([
                'field_name' => 'avg_rating',
                'field_label' => 'Average Rating',
                'field_type' => 'varchar',
                'weight' => 0,
                'source_type' => 'callback',
                'source_callback' => 'Sellvana_ProductReviews_Model_Review::indexAvgRating',
                'filter_type' => 'exclusive',
                'filter_counts' => 1,
                'filter_order' => 10,
            ])->save();
        }
    }

    public function upgrade__0_5_0_0__0_5_1_0()
    {
        $table = $this->Sellvana_ProductReviews_Model_Review->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'rating1'           => BDb::DROP,
                'rating2'           => BDb::DROP,
                'rating3'           => BDb::DROP,
                'verified_purchase' => 'tinyint(1) unsigned not null after rating'
            ],
        ]);
    }

    public function upgrade__0_6_0_0__0_6_1_0()
    {
        /** @see http://planspace.org/2014/08/17/how-to-sort-by-average-rating/ */

        $tProduct = $this->Sellvana_Catalog_Model_Product->table();
        $this->BDb->ddlTableDef($tProduct, [
            BDb::COLUMNS => [
                'num_upvotes' => 'int null',
            ],
        ]);

        $this->Sellvana_CatalogIndex_Model_Field->load('avg_rating', 'field_name')->set([
            'sort_method' => 'decimal',
            'sort_callback' => 'Sellvana_ProductReviews_Model_Review::indexAvgRatingLaplace',
        ])->save();

        $tReview = $this->Sellvana_ProductReviews_Model_Review->table();
        $this->BDb->run("UPDATE {$tProduct} p SET p.num_upvotes=(select sum(rating) from {$tReview} r where r.product_id=p.id)");
    }
}
