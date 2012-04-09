<?php

class FCom_IndexTank_Frontend extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /indextank/search', 'FCom_IndexTank_Frontend_Controller.search')
        ;

        BLayout::i()->addAllViews('Frontend/views');

        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_IndexTank_Frontend::layout');
    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            '/indextank/search'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('indextank/search'))
            )
        ));
    }
}