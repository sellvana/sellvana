<?php

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

        BLayout::i()->addAllViews('Admin/views');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'admin/header', 'do'=>array(
                    array('addNav', 'catalog/prodreviews', array('label'=>'Product Reviews',
                        'href'=>BApp::href('prodreviews'))),
                )),
            ),
        ));
    }
}
