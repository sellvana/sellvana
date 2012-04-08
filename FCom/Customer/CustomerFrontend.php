<?php

class FCom_Customer_Frontend extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Customer_Frontend::layout')
        ;

        BFrontController::i()
            ->route('GET /customers', 'FCom_Customer_Frontend_Controller.index')
        ;

        BLayout::i()->addAllViews('Frontend/views');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            '/customer'=>array(

            ),
        ));
    }
}