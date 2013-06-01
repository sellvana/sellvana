<?php

class FCom_Catalog_Frontend extends BClass
{
    static public function bootstrap()
    {
        BRouting::i()
                //search
            ->get('/*category', 'FCom_Catalog_Frontend_Controller_Search.category')
            ->get('/catalog/search', 'FCom_Catalog_Frontend_Controller_Search.search')
                //catalog
            ->get('/*manuf', 'FCom_Catalog_Frontend_Controller.manuf')
            ->get('/:product', 'FCom_Catalog_Frontend_Controller.product')
            ->post('/:product', 'FCom_Catalog_Frontend_Controller.product_post')
            ->get('/*category/:product', 'FCom_Catalog_Frontend_Controller.product')
            ->get('/catalog/compare', 'FCom_Catalog_Frontend_Controller.compare')
        ;

        BLayout::i()->addAllViews('Frontend/views')
            ->loadLayoutAfterTheme('Frontend/layout.yml');
    }
}