<?php

class FCom_Catalog_Frontend extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /c/*category', 'FCom_Catalog_Frontend_Controller.category')
            ->route( 'GET /m/*manuf', 'FCom_Catalog_Frontend_Controller.manuf')
            ->route( 'GET /p/*product', 'FCom_Catalog_Frontend_Controller.product')
            ->route( 'GET /search', 'FCom_Catalog_Frontend_Controller.search')
            ->route( 'GET /compare', 'FCom_Catalog_Frontend_Controller.compare')
        ;

        BLayout::i()->allViews('Frontend/views', 'catalog/');
    }
}