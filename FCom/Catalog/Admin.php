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
            ->route('GET /products/grid_data', 'FCom_Catalog_Admin_Controller_Products.grid_data')
            ->route('GET /products/form/:id', 'FCom_Catalog_Admin_Controller_Products.form')
            ->route('GET /products/form_tab/:id', 'FCom_Catalog_Admin_Controller_Products.form_tab')
            ->route('POST /products/form/:id', 'FCom_Catalog_Admin_Controller_Products.form_post')

            ->route('GET /products/edit/:id', 'FCom_Catalog_Admin_Controller_Products.edit')

            ->route('GET /categories', 'FCom_Catalog_Admin_Controller_Categories.index')
            ->route('GET /api/category_tree', 'FCom_Catalog_Admin_Controller_Categories.category_tree_get')
            ->route('POST /api/category_tree', 'FCom_Catalog_Admin_Controller_Categories.category_tree_post')
        ;

        BLayout::i()
            ->view('catalog/products/form', array('view_class'=>'FCom_Admin_View_Form'))
            ->allViews('Admin/views', 'catalog')
        ;

        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Catalog_Admin::layout')
            ->on('category_tree_post.associate.products', 'FCom_Catalog_Model_Product.onAssociateCategory')
            ->on('category_tree_post.reorderAZ', 'FCom_Catalog_Model_Category.onReorderAZ')

            ->on('FCom_Catalog_Admin_Controller_Products::action_edit_post', 'FCom_Catalog_Admin.onProductsEditPost')

            /** @todo initialize these events only when needed */
            ->on('FCom_Admin_View_MediaLibrary::gridConfig.media/product/attachment',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridConfig', array('type'=>'A'))

            ->on('FCom_Admin_Controller_MediaLibrary::action_grid_get.media/product/attachment.orm',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridGetORM', array('type'=>'A'))

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost.media/product/attachment.upload',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridUpload', array('type'=>'A'))

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost.media/product/attachment.edit',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridEdit', array('type'=>'A'))

            ->on('FCom_Admin_View_MediaLibrary::gridConfig.media/product/image',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridConfig', array('type'=>'I'))

            ->on('FCom_Admin_Controller_MediaLibrary::action_grid_get.media/product/image.orm',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridGetORM', array('type'=>'I'))

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost.media/product/image.upload',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridUpload', array('type'=>'I'))

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost.media/product/image.edit',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridEdit', array('type'=>'I'))
        ;

        FCom_Admin_Controller_MediaLibrary::i()
            ->allowFolder('media/product/image')
            ->allowFolder('media/product/attachment')
        ;
    }

    static public function layout()
    {
        $baseHref = BApp::m('FCom_Catalog')->baseHref();
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'root', 'do'=>array(
                        array('addNav', 'catalog', array('label'=>'Catalog', 'pos'=>100)),
                        array('addNav', 'catalog/products', array('label'=>'Products', 'href'=>$baseHref.'/products')),
                        array('addNav', 'catalog/categories', array('label'=>'Categories', 'href'=>$baseHref.'/categories')),
                        array('addNav', 'catalog/product_families', array('label'=>'Product Families', 'href'=>$baseHref.'/product_families')),
                        array('addNav', 'catalog/product_reviews', array('label'=>'Product Reviews', 'href'=>$baseHref.'/product_reviews')),
                    )),
                ),
                'catalog_product_form_tabs'=>array(
                    array('view', 'catalog/products/form',
                        'set'=>array(
                            'tab_view_prefix' => 'catalog/products/tab/',
                        ),
                        'do'=>array(
                            array('addTab', 'main', array('label' => 'General Info')),
                            array('addTab', 'attributes', array('label' => 'Attributes')),
                            array('addTab', 'linked-products', array('label' => 'Linked Products', 'async'=>true)),
                            array('addTab', 'categories', array('label' => 'Categories', 'async'=>true)),
                            array('addTab', 'attachments', array('label' => 'Attachments', 'async'=>true)),
                            array('addTab', 'images', array('label' => 'Images', 'async'=>true)),
                            array('addTab', 'vendor-data', array('label' => 'Vendor Data', 'async'=>true)),
                            array('addTab', 'product-reviews', array('label' => 'Product Reviews', 'async'=>true)),
                            array('addTab', 'promotions', array('label' => 'Promotions', 'async'=>true)),
                        ),
                    ),
                ),
                '/catalog/products'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('catalog/products')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/products'))),
                ),
                '/catalog/products/form'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('catalog/products/form')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/products'))),
                    array('layout', 'form'),
                    array('layout', 'catalog_product_form_tabs'),
                ),
                '/catalog/categories'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('catalog/categories')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/categories'))),
                ),
                '/catalog/categories/form'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('catalog/categories/form')),
                ),
            ));
        ;
    }

    public function onProductsEditPost($args)
    {
print_r($args); exit;
    }
}