<?php

class FCom_ProductReviews_Model_Review extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_review';
    protected static $_origClass = __CLASS__;

    protected static $_config = [
       'max' => 5,
       'min' => 0,
       'step' => 1,
    ];

    protected static $_validationRules = [
        [ 'product_id', '@required' ],
        [ 'customer_id', '@required' ],
        [ 'rating', '@required' ],
        [ 'title', '@required' ],

        [ 'title', '@string', null, [ 'max' => 255 ] ],

        /*array('helpful_voices', '@string', null, array('max' => 11)),
        array('rating', '@integer'),
        array('rating1', '@integer'),
        array('rating2', '@integer'),
        array('rating3', '@integer'),
        array('approved', '@integer'),
        array('helpful', '@integer'),
        array('offensive', '@integer'),
        array('offensive', '@integer'),*/
    ];

    public function notify()
    {
        BLayout::i()->view( 'email/prodreview-new-admin' )->email();
        BLayout::i()->view( 'email/prodreview-new-customer' )->email();
        return $this;
    }

    public function confirm()
    {
        $this->set( 'approved', 1 )->save();
        BLayout::i()->view( 'email/prodreview-confirm-customer' )->email();
        return $this;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        //TODO: condition on relevant changes only (approved, rating)
        $pId = $this->get( 'product_id' );
        $rating = static::i()->orm()->where( 'product_id', $pId )
            ->where( 'approved', 1 )
            ->select( '(avg(rating))', 'avg' )
            #->select('(avg(rating1))', 'avg1')
            #->select('(avg(rating2))', 'avg2')
            #->select('(avg(rating3))', 'avg3')
            ->select( '(count(1))', 'num' )
            ->find_one();
        FCom_Catalog_Model_Product::i()->load( $pId )
            ->set( 'avg_rating', $rating->get( 'avg' ) )
            ->set( 'num_reviews', $rating->get( 'num' ) )
            ->save();
        return $this;
    }

    public function helpful( $mark )
    {
        $this->add( 'helpful', $mark );
        $this->add( 'helpful_voices' );
        $this->save();
        return $this;
    }

    public function config()
    {
        return static::$_config;
    }

    static public function indexAvgRating( $products, $field )
    {
        $data = [];
        foreach ( $products as $p ) {
            $m = $p->avg_rating;
            if     ( $m >= 4 ) $v = '4 ==> 4 Stars & Up';
            elseif ( $m >= 3 ) $v = '3 ==> 3 Stars & Up';
            elseif ( $m >= 2 ) $v = '2 ==> 2 Stars & Up';
            elseif ( $m >= 1 ) $v = '1 ==> 1 Star & Up';
            else $v = '';
            $data[ $p->id() ] = $v;
        }
        return $data;
    }
}
