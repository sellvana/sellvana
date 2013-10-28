<?php

class FCom_ProductReviews_Model_Review extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_review';
    protected static $_origClass = __CLASS__;

	protected $_validationRules = array(
		array('product_id', '@required'),
		array('customer_id', '@required'),
		array('rating', '@required'),
		array('title', '@required'),
		array('create_at', '@required'),

        array('rating', '@integer'),
        array('rating1', '@integer'),
        array('rating2', '@integer'),
        array('rating3', '@integer'),
		array('approved', '@integer'),
		array('helpful', '@integer'),
		array('offensive', '@integer'),
	);

    public function notify()
    {
        BLayout::i()->view('email/prodreview-new-admin')->email();
        BLayout::i()->view('email/prodreview-new-customer')->email();
        return $this;
    }

    public function confirm()
    {
        $this->set('approved', 1)->save();
        $this->udpateProductStats();
        BLayout::i()->view('email/prodreview-confirm-customer')->email();
        return $this;
    }

    public function updateProductStats()
    {
        $rating = static::i()->orm()->where('product_id', $this->product_id)
            ->where('approved', 1)
            ->select('(avg(rating))', 'avg')
            ->select('(count(1))', 'num')
            ->find_one();
        FCom_Catalog_Model_Product::i()->load($this->product_id)
            ->set('avg_rating', $rating->get('avg'))
            ->set('num_reviews', $rating->get('num'))
            ->save();
        return $this;
    }

    public function helpful($mark)
    {
        $this->add('helpful', $mark);
        $this->add('helpful_voices');
        $this->save();
        return $this;
    }
}
