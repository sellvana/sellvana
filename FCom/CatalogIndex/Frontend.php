<?php

class FCom_CatalogIndex_Frontend extends BClass
{
    /**
     * Bootstrap CatalogIndex routes, events and layout for Frontend part
     */
    static public function bootstrap()
    {
        FCom_CatalogIndex_Main::bootstrap();

        BRouting::i()
            ->get('/catalogindex/search', 'FCom_CatalogIndex_Frontend_Controller.search')
            //->route('^GET /([a-z0-9/-]+)/filters=([a-z0-9+.-]+)$', 'FCom_CatalogIndex_Frontend_Controller.category')
            ->get('/index-test', 'FCom_CatalogIndex_Frontend_Controller.test')
            ->get('/index-reindex', 'FCom_CatalogIndex_Frontend_Controller.reindex')
        ;

        BLayout::i()
            ->addAllViews('Frontend/views')
            ->afterTheme('FCom_CatalogIndex_Frontend::layout');

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