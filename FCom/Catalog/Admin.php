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
            ->route('GET /products/view_tab/:id', 'FCom_Catalog_Admin_Controller_Products.view_tab')
            ->route('POST /products/edit/:id', 'FCom_Catalog_Admin_Controller_Products.edit_post')

            ->route('GET /products/edit/:id', 'FCom_Catalog_Admin_Controller_Products.edit')


            ->route('GET /categories', 'FCom_Catalog_Admin_Controller_Categories.index')
            ->route('GET /api/category_tree', 'FCom_Catalog_Admin_Controller_Categories.category_tree_get')
            ->route('POST /api/category_tree', 'FCom_Catalog_Admin_Controller_Categories.category_tree_post')
        ;

        BLayout::i()
            ->view('catalog/products/view', array('view_class'=>'FCom_Catalog_Admin_View_ProductView'))
            ->allViews('Admin/views', 'catalog')
        ;

        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Catalog_Admin::layout')
            ->on('category_tree_post.associate.products', 'FCom_Catalog_Model_Product.onAssociateCategory')
            ->on('category_tree_post.reorderAZ', 'FCom_Catalog_Model_Category.onReorderAZ')

            ->on('FCom_Catalog_Admin_Controller_Products::edit_post', 'FCom_Catalog_Admin.onProductsEditPost')
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
                        array('navAdd', 'catalog/products_view', array('label'=>'Product View', 'href'=>$baseHref.'/products/view/1')),
                    )),
                ),
                'catalog_product_view_tabs'=>array(
                    array('view', 'catalog/products/view', 'do'=>array(
                        array('addTab', 'general-info', array('label' => 'General Info')),
                        array('addTab', 'attributes', array('label' => 'Attributes')),
                        array('addTab', 'related-products', array('label' => 'Related Products')),
                        array('addTab', 'family-products', array('label' => 'Family Products')),
                        array('addTab', 'similar-products', array('label' => 'Similar Products')),
                        array('addTab', 'categories', array('label' => 'Categories', 'async'=>true)),
                        array('addTab', 'attachments', array('label' => 'Attachments')),
                        array('addTab', 'images', array('label' => 'Images')),
                        array('addTab', 'vendor-data', array('label' => 'Vendor Data')),
                        array('addTab', 'product-reviews', array('label' => 'Product Reviews')),
                        array('addTab', 'promotions', array('label' => 'Promotions')),
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
                    array('view', 'head', 'do'=>array(
                        array('js', '{FCom_Core}/js/lib/ckeditor/ckeditor_source.js', array()),
                        array('js', '{FCom_Core}/js/lib/jquery.cookie.js', array()),
                        array('js', '{FCom_Core}/js/lib/jquery.hotkeys.js', array()),
                        array('js', '{FCom_Core}/js/lib/jquery.jstree.js', array()),
                        array('css', '{FCom_Core}/js/lib/themes/default/style.css', array()),
                    )),
                    array('layout', 'catalog_product_view_tabs'),
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

    public function onProductsEditPost($args)
    {
print_r($args); exit;
    }
}