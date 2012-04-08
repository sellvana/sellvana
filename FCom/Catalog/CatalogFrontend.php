<?php

class FCom_Catalog_Frontend extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /c/*category', 'FCom_Catalog_Frontend_Controller.category')
            ->route( 'GET /m/*manuf', 'FCom_Catalog_Frontend_Controller.manuf')
            ->route( 'GET /p/*product', 'FCom_Catalog_Frontend_Controller.product')
            ->route( 'GET /catalog/search', 'FCom_Catalog_Frontend_Controller.search')
            ->route( 'GET /catalog/compare', 'FCom_Catalog_Frontend_Controller.compare')
        ;

        BLayout::i()->allViews('Frontend/views', 'catalog/');

        BPubSub::i()->on('BLayout::layout.load.after', 'FCom_Catalog_Frontend::layout');
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