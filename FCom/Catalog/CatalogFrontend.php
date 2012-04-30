<?php

class FCom_Catalog_Frontend extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /*category', 'FCom_Catalog_Frontend_Controller.category')
            ->route( 'GET /*manuf', 'FCom_Catalog_Frontend_Controller.manuf')
            ->route( 'GET /:product', 'FCom_Catalog_Frontend_Controller.product')
            ->route( 'GET /*category/:product', 'FCom_Catalog_Frontend_Controller.product')
            ->route( 'GET /catalog/search', 'FCom_Catalog_Frontend_Controller.search')
            ->route( 'GET /catalog/compare', 'FCom_Catalog_Frontend_Controller.compare')
        ;

        BLayout::i()->addAllViews('Frontend/views')
            ->afterTheme('FCom_Catalog_Frontend::layout');
    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            '/catalog/category'=>array(
                array('layout', 'base'),
            ),

            '/catalog/product'=>array(
                array('layout', 'base'),
            ),

            '/catalog/search'=>array(
                array('layout', 'base'),
            ),

            '/catalog/compare'=>array(

            ),

            '/catalog/compare/ajax'=>array(

            ),
        ));
    }
}