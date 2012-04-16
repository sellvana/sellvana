<?php

class FCom_Cms_Frontend extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Cms_Frontend::layout')
        ;

        BFrontController::i()
            ->route('GET /*page', 'FCom_Cms_Frontend_Controller.index')
        ;

        BLayout::i()->addAllViews('Frontend/views');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            '/cms'=>array(

            ),
        ));
    }
}
