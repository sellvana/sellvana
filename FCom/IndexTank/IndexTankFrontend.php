<?php

class FCom_IndexTank_Frontend extends BClass
{
    /**
     * Bootstrap IndexTank routes, events and layout for Frontend part
     */
    static public function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /indextank/search', 'FCom_IndexTank_Frontend_Controller.search')
        ;

        BLayout::i()->addAllViews('Frontend/views');

        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_IndexTank_Frontend::layout');

        BClassRegistry::i()->overrideClass('FCom_Catalog_Frontend_Controller_Search', 'FCom_IndexTank_Frontend_Controller');
    }

    /**
     * Itialized base layout, navigation links and page views scripts
     */
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