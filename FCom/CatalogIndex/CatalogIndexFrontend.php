<?php

require_once __DIR__.'/CatalogIndex.php';

class FCom_CatalogIndex_Frontend extends BClass
{
    /**
     * Bootstrap CatalogIndex routes, events and layout for Frontend part
     */
    static public function bootstrap()
    {
        FCom_CatalogIndex::bootstrap();

        BFrontController::i()
            ->route( 'GET /catalogindex/search', 'FCom_CatalogIndex_Frontend_Controller.search')
            ->route('GET /index-test', 'FCom_CatalogIndex_Frontend_Controller.test')
        ;

        BLayout::i()->addAllViews('Frontend/views');

        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_CatalogIndex_Frontend::layout');

        BClassRegistry::i()->overrideClass('FCom_Catalog_Frontend_Controller_Search', 'FCom_CatalogIndex_Frontend_Controller');
    }

    /**
     * Itialized base layout, navigation links and page views scripts
     */
    static public function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'head', 'do'=>array(
                    //array('js', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/jquery-ui.min.js')
                )
            )),
            '/catalog/category'=>array(
                array('layout', 'base'),
                array('view', 'root', 'set'=>array('show_left_col'=>true)),
                array('hook', 'sidebar-left', 'views'=>array('catalogindex/product/filters'))
            ),
            '/catalog/search'=>array(
                array('layout', 'base'),
                array('view', 'root', 'set'=>array('show_left_col'=>true)),
                array('hook', 'sidebar-left', 'views'=>array('catalogindex/product/filters'))
            ),

        ));
    }
}