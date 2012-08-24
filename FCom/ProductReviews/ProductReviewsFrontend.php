<?php

class FCom_ProductReviews_Frontend extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_ProductReviews_Frontend::layout')
            ->on('BLayout::hook.prodreviews-reviews', 'FCom_ProductReviews_Frontend.hookReviews')
        ;

        BFrontController::i()
            ->route('GET /prodreviews', 'FCom_ProductReviews_Frontend_Controller.index')
            ->route('GET|POST /prodreviews/.action', 'FCom_ProductReviews_Frontend_Controller')
        ;

        BLayout::i()->addAllViews('Frontend/views');
    }

    public function hookReviews($args)
    {
        $product = $args['product'];
        $productReviews = FCom_ProductReviews_Model_Reviews::i()->orm()->where("product_id", $product->id())->find_many();
        BLayout::i()->view('prodreviews/reviews')->product_reviews = $productReviews;
        BLayout::i()->view('prodreviews/reviews')->product = $product;
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'head', 'do'=>array(
                    array('js', '{FCom_ProductReviews}/Frontend/js/fcom.productreviews.js'),
                )
            )),
            '/prodreviews/add'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('prodreviews/add')),
            ),
            '/catalog/product'=>array(
                array('hook', 'prodreviews-reviews', 'views'=>array('prodreviews/reviews')),
            ),
        ));
    }
}
