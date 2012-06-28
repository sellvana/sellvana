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
            ->route( 'GET /:product/review/add', 'FCom_Catalog_Frontend_Controller_Review.add')
            ->route( 'POST /:product/review/add', 'FCom_Catalog_Frontend_Controller_Review.add_post')
            ->route( 'POST /:product/review/helpful', 'FCom_Catalog_Frontend_Controller_Review.helpful_post')
        ;

        BLayout::i()->addAllViews('Frontend/views')
            ->afterTheme('FCom_Catalog_Frontend::layout');
    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'head', 'do'=>array(
                    array('js', '{FCom_Catalog}/Frontend/js/fcom.frontend.js'),
                )
            )),
            '/catalog/category'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('catalog/category'))
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

            ),

            '/catalog/compare/ajax'=>array(

            ),
        ));
    }
}