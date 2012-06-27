<?php

class FCom_Catalog_Model_ProductReview extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_review';
    protected static $_origClass = __CLASS__;

    public function add($customerId, $productId, $dataInput)
    {
        $data = array(
            'customer_id' => $customerId,
            'product_id' => $productId,
            'text'  => $dataInput['text'],
            'title' => $dataInput['title'],
            'rating' => $dataInput['rating']
        );
        $review = self::create($data);
        $review->save();
        return $review;
    }

    public static function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `rating` tinyint(1) unsigned not null,
  `title` varchar(255) NOT NULL,
  `text` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}