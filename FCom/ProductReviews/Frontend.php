<?php

class FCom_ProductReviews_Frontend extends BClass
{
    public static function bootstrap()
    {
        BEvents::i()
            ->on('BLayout::hook.prodreviews-reviews', 'FCom_ProductReviews_Frontend.hookReviews')
        ;

        BRouting::i()
            ->get('/prodreviews', 'FCom_ProductReviews_Frontend_Controller.index')
            ->any('/prodreviews/.action', 'FCom_ProductReviews_Frontend_Controller')
        ;

        BLayout::i()->addAllViews('Frontend/views')
            ->loadLayoutAfterTheme('Frontend/layout.yml');
    }

    public function hookReviews($args)
    {
        $product = $args['product'];
        $productReviews = FCom_ProductReviews_Model_Reviews::i()->orm()->where("product_id", $product->id())->find_many();
        BLayout::i()->view('prodreviews/reviews')->product_reviews = $productReviews;
        BLayout::i()->view('prodreviews/reviews')->product = $product;
        return BLayout::i()->view('prodreviews/reviews')->render();
    }
}
