<?php

class FCom_ProductReviews extends BClass
{
    public static function bootstrap()
    {
        switch (FCom::area()) {
            case 'FCom_Frontend': FCom_ProductReviews_Frontend::bootstrap(); break;
            case 'FCom_Admin': FCom_ProductReviews_Admin::bootstrap(); break;
        }
    }
}

class FCom_ProductReviews_Frontend extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_ProductReviews_Frontend::layout')
        ;

        BFrontController::i()
            ->route('GET /prodreviews', 'FCom_ProductReviews_Frontend_Controller.index')
        ;

        BLayout::i()->allViews('Frontend/views', 'prodreviews');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            '/prodreviews'=>array(

            ),
        ));
    }
}

class FCom_ProductReviews_Admin extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_ProductReviews_Admin::layout')
        ;

        BFrontController::i()
            ->route('GET /prodreviews', 'FCom_ProductReviews_Admin_Controller.index')
        ;

        BLayout::i()->allViews('Admin/views', 'prodreviews');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'root', 'do'=>array(
                    array('addNav', 'catalog/prodreviews', array('label'=>'Product Reviews',
                        'href'=>BApp::href('prodreviews'))),
                )),
            ),
        ));
    }
}