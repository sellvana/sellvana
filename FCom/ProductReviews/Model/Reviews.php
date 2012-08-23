<?php

class FCom_ProductReviews_Model_Reviews extends FCom_Core_Model_Abstract
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
        $review = static::create($data);
        $review->save();
        $avg = static::i()->orm()->where('product_id', $productId)->select('(avg(rating))', 'value')->find_one();
        FCom_Catalog_Model_Product::i()->load($productId)->set('avg_rating', $avg->value)->save();
        return $review;
    }

    public function helpful($mark)
    {
        $this->helpful += $mark;
        $this->helpful_voices++;
        $this->save();
    }
}