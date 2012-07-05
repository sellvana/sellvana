<?php

class FCom_ProductReviews_Frontend extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_ProductReviews_Frontend::layout')
        ;

        BFrontController::i()
            ->route('GET /prodreviews', 'FCom_ProductReviews_Frontend_Controller.index')
            ->route( 'GET /prodreviews/add', 'FCom_ProductReviews_Frontend_Controller.add')
            ->route( 'POST /prodreviews/add', 'FCom_ProductReviews_Frontend_Controller.add_post')
            ->route( 'POST /prodreviews/helpful', 'FCom_ProductReviews_Frontend_Controller.helpful_post')
        ;

        BLayout::i()->addAllViews('Frontend/views');
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
                array('hook', 'main', 'views'=>array('prodreviews/add'))
            ),
        ));
    }
}
