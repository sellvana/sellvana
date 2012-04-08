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
        ;

        BLayout::i()->addAllViews('Frontend/views');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            '/prodreviews'=>array(

            ),
        ));
    }
}
