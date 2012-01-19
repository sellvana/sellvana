<?php

class FCom_Catalog_Admin extends BClass
{
    static public function bootstrap()
    {
        $module = BApp::m();
        $module->base_href = BApp::baseUrl().'/catalog';
        $module->base_src .= '/Admin';

        BDb::migrate('FCom_Catalog_Migrate');

        BFrontController::i()
            ->route('GET /products', 'FCom_Catalog_Admin_Controller_Products.index')
            ->route('GET /products/grid/config', 'FCom_Catalog_Admin_Controller_Products.grid_config')
            ->route('GET /products/grid/data', 'FCom_Catalog_Admin_Controller_Products.grid_data')
            ->route('GET /products/view/:id', 'FCom_Catalog_Admin_Controller_Products.view')
            ->route('GET /categories', 'FCom_Catalog_Admin_Controller_Categories.index')
            ->route('GET /api/category_tree', 'FCom_Catalog_Admin_Controller_Categories.category_tree_get')
            ->route('POST /api/category_tree', 'FCom_Catalog_Admin_Controller_Categories.category_tree_post')
        ;

        BLayout::i()
            ->allViews('Admin/views', 'catalog')
        ;

        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Catalog_Admin::layout')
            ->on('category_tree_post.associate.products', 'FCom_Catalog_Model_Product.onAssociateCategory')
            ->on('category_tree_post.reorderAZ', 'FCom_Catalog_Model_Category.onReorderAZ')
        ;
    }

    static public function layout()
    {
        $baseHref = BApp::m('FCom_Catalog')->baseHref();
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'root', 'do'=>array(
                        array('navAdd', 'catalog', array('label'=>'Catalog')),
                        array('navAdd', 'catalog/products', array('label'=>'Products', 'href'=>$baseHref.'/products')),
                        array('navAdd', 'catalog/products_view', array('label'=>'Product Edit', 'href'=>$baseHref.'/products/view/123')),
                    )),
                ),
                '/catalog/products'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('catalog/products')),
                    array('view', 'root', 'do'=>array(array('navCur', 'catalog/products'))),
                ),
                '/catalog/products/view'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('catalog/products/view')),
                    array('view', 'root', 'do'=>array(array('navCur', 'catalog/products_view'))),
                ),
                '/catalog/categories'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('catalog/categories')),
                ),
                '/catalog/categories/view'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('catalog/categories/view')),
                ),
            ));
        ;
    }

}