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
            ->route('GET /catalogindex/search', 'FCom_CatalogIndex_Frontend_Controller.search')
            ->route('^GET /([a-z0-9/-]+)/filters=([a-z0-9+.-]+)$', 'FCom_CatalogIndex_Frontend_Controller.category')
            ->route('GET /index-test', 'FCom_CatalogIndex_Frontend_Controller.test')
        ;

        BLayout::i()->addAllViews('Frontend/views');

        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_CatalogIndex_Frontend::layout');

        BClassRegistry::i()->overrideClass('FCom_Catalog_Frontend_Controller_Search', 'FCom_CatalogIndex_Frontend_Controller');
    }

    /**
     * Initialized base layout, navigation links and page views scripts
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
                array('hook', 'catalog/product/pager.fields', 'views'=>array('catalog/category/_pager_fields')),
            ),
            '/catalog/search'=>array(
                array('hook', 'catalog/product/pager.fields', 'views'=>array('catalog/category/_pager_fields')),
            ),
        ));
    }
}