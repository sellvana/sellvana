<?php

class FCom_ProductReviews_Frontend extends BClass
{
    public function hookReviews( $args )
    {
        $product = $args[ 'product' ];
        $productReviews = FCom_ProductReviews_Model_Review::i()->orm()->where( "product_id", $product->id() )->find_many();
        BLayout::i()->view( 'prodreviews/reviews' )->product_reviews = $productReviews;
        BLayout::i()->view( 'prodreviews/reviews' )->product = $product;
        return BLayout::i()->view( 'prodreviews/reviews' )->render();
    }
}
