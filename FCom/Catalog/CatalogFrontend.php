<?php

class FCom_Catalog_Frontend extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
                //search
            ->route( 'GET /*category', 'FCom_Catalog_Frontend_Controller_Search.category')
            ->route( 'GET /catalog/search', 'FCom_Catalog_Frontend_Controller_Search.search')
                //catalog
            ->route( 'GET /*manuf', 'FCom_Catalog_Frontend_Controller.manuf')
            ->route( 'GET /:product', 'FCom_Catalog_Frontend_Controller.product')
            ->route( 'POST /:product', 'FCom_Catalog_Frontend_Controller.product_post')
            ->route( 'GET /*category/:product', 'FCom_Catalog_Frontend_Controller.product')
            ->route( 'GET /catalog/compare', 'FCom_Catalog_Frontend_Controller.compare')

            //api route for category
            ->route( 'GET|POST /v1/catalog/category', 'FCom_Catalog_ApiServer_V1_Category')
            ->route( 'GET|POST|DELETE|PUT /v1/catalog/category/:id', 'FCom_Catalog_ApiServer_V1_Category')

            //api route for product
            ->route( 'GET|POST /v1/catalog/product', 'FCom_Catalog_ApiServer_V1_Product')
            ->route( 'GET|POST|DELETE|PUT /v1/catalog/product/:id', 'FCom_Catalog_ApiServer_V1_Product')
        ;

        BLayout::i()->addAllViews('Frontend/views')
            ->afterTheme('FCom_Catalog_Frontend::layout');
    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'head', 'do'=>array(
                    array('js', '{FCom_Catalog}/Frontend/js/fcom.catalog.js'),
                )
            )),
            '/catalog/category'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('catalog/category')),
            ),

            '/catalog/product'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('catalog/product'))
            ),

            '/catalog/search'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('catalog/search'))
            ),

            '/catalog/review/add'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('catalog/review/add'))
            ),

            '/catalog/compare'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('catalog/compare')),
            ),

            '/catalog/compare/xhr'=>array(
                array('root', 'catalog/compare'),
            ),
        ));
    }
}