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
                array('view', 'root', 'set'=>array('show_left_col'=>true)),
                array('hook', 'main', 'views'=>array('catalog/category')),
                array('hook', 'sidebar-left', 'views'=>array('catalog/category/sidebar')),
            ),

            '/catalog/product'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('catalog/product'))
            ),

            '/catalog/search'=>array(
                array('layout', 'base'),
                array('view', 'root', 'set'=>array('show_left_col'=>true)),
                array('hook', 'main', 'views'=>array('catalog/search')),
                array('hook', 'sidebar-left', 'views'=>array('catalog/category/sidebar')),
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