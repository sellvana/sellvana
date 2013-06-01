<?php

class FCom_IndexTank_Frontend extends BClass
{
    /**
     * Bootstrap IndexTank routes, events and layout for Frontend part
     */
    static public function bootstrap()
    {
        BRouting::i()
            ->route( 'GET /indextank/search', 'FCom_IndexTank_Frontend_Controller.search')
        ;

        BEvents::i()
            ->on('FCom_IndexTank_Index_Product::add', 'FCom_IndexTank_Index_Product::onProductIndexAdd');

        BLayout::i()->addAllViews('Frontend/views')
            ->loadLayoutAfterTheme('Frontend/layout.yml');

        BClassRegistry::i()->overrideClass('FCom_Catalog_Frontend_Controller_Search', 'FCom_IndexTank_Frontend_Controller');
    }

}