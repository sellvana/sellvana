<?php

class FCom_Catalog_Admin extends BClass
{
    static public function bootstrap()
    {
        $module = BApp::m();
        $module->base_src .= '/Admin';

        BFrontController::i()
            ->route('GET /catalog/categories', 'FCom_Catalog_Admin_Controller_Categories.index')
            ->route('GET|POST /catalog/categories/tree_data', 'FCom_Catalog_Admin_Controller_Categories.tree_data')
            ->route('GET|POST /catalog/categories/tree_form/:id', 'FCom_Catalog_Admin_Controller_Categories.tree_form')

            ->route('GET /catalog/products', 'FCom_Catalog_Admin_Controller_Products.index')
            ->route('GET /catalog/products/grid_data', 'FCom_Catalog_Admin_Controller_Products.grid_data')
            ->route('GET /catalog/products/subgrid_data', 'FCom_Catalog_Admin_Controller_Products.subgrid_data')
            ->route('GET|POST /catalog/products/form/:id', 'FCom_Catalog_Admin_Controller_Products.form')

            ->route('GET /catalog/families', 'FCom_Catalog_Admin_Controller_Families.index')
            ->route('GET /catalog/families/grid_data', 'FCom_Catalog_Admin_Controller_Families.grid_data')
            ->route('GET|POST /catalog/families/form/:id', 'FCom_Catalog_Admin_Controller_Families.form')
            ->route('GET /catalog/families/autocomplete', 'FCom_Catalog_Admin_Controller_Families.autocomplete')
            ->route('GET /catalog/families/product_data', 'FCom_Catalog_Admin_Controller_Families.product_data')
        ;

        BLayout::i()
            ->addAllViews('Admin/views')
            ->afterTheme('FCom_Catalog_Admin::layout')
        ;

        BPubSub::i()
            ->on('category_tree_post.associate.products', 'FCom_Catalog_Model_Product.onAssociateCategory')
            ->on('category_tree_post.reorderAZ', 'FCom_Catalog_Model_Category.onReorderAZ')

            ->on('FCom_Catalog_Admin_Controller_Products::action_edit_post', 'FCom_Catalog_Admin::onProductsEditPost')

            /** @todo initialize these events only when needed */
            ->on('FCom_Admin_Controller_MediaLibrary::gridConfig.media/product/attachment',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridConfig', array('type'=>'A'))

            ->on('FCom_Admin_Controller_MediaLibrary::action_grid_get.media/product/attachment.orm',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridGetORM', array('type'=>'A'))

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost.media/product/attachment.upload',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridUpload', array('type'=>'A'))

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost.media/product/attachment.edit',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridEdit', array('type'=>'A'))

            ->on('FCom_Admin_Controller_MediaLibrary::gridConfig.media/product/image',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridConfig', array('type'=>'I'))

            ->on('FCom_Admin_Controller_MediaLibrary::action_grid_get.media/product/image.orm',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridGetORM', array('type'=>'I'))

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost.media/product/image.upload',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridUpload', array('type'=>'I'))

            ->on('FCom_Admin_Controller_MediaLibrary::processGridPost.media/product/image.edit',
                'FCom_Catalog_Admin_Controller_Products.onMediaGridEdit', array('type'=>'I'))

            ->on('FCom_Cms_Admin_Controller_Nav::action_tree_form', 'FCom_Catalog_Admin::onNavTreeForm')
        ;

        FCom_Admin_Controller_MediaLibrary::i()
            ->allowFolder('media/product/image')
            ->allowFolder('media/product/attachment')
        ;

        FCom_Admin_Model_Role::i()->createPermission(array(
            'catalog' => 'Catalog',
            'catalog/products' => 'Manage Products',
            'catalog/categories' => 'Manage Categories',
            'catalog/families' => 'Manage Families',
        ));

    }

    static public function layout()
    {
        $baseHref = BApp::href('catalog');
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'root', 'do'=>array(
                        array('addNav', 'catalog', array('label'=>'Catalog', 'pos'=>100)),
                        array('addNav', 'catalog/categories', array('label'=>'Categories', 'href'=>$baseHref.'/categories')),
                        array('addNav', 'catalog/products', array('label'=>'Products', 'href'=>$baseHref.'/products')),
                        array('addNav', 'catalog/families', array('label'=>'Product Families', 'href'=>$baseHref.'/families')),
                        array('addQuickSearch', 'catalog/products', array('label'=>'Products', 'href'=>$baseHref.'/products')),
                        array('addShortcut', 'catalog/products', array('label'=>'New Product', 'href'=>$baseHref.'/products/form/')),
                    )),
                ),
                'catalog_product_form_tabs'=>array(
                    array('view', 'catalog/products-form',
                        'set'=>array(
                            'tab_view_prefix' => 'catalog/products-form/',
                        ),
                        'do'=>array(
                            array('addTab', 'main', array('label'=>'General Info', 'pos'=>10)),
                            array('addTab', 'linked-products', array('label'=>'Linked Products', 'pos'=>20, 'async'=>true)),
                            array('addTab', 'categories', array('label'=>'Categories', 'pos'=>30, 'async'=>true)),
                            array('addTab', 'attachments', array('label'=>'Attachments', 'pos'=>40, 'async'=>true)),
                            array('addTab', 'images', array('label'=>'Images', 'pos'=>50, 'async'=>true)),
                            array('addTab', 'product-reviews', array('label'=>'Product Reviews', 'pos'=>70, 'async'=>true)),
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
                    array('hook', 'main', 'views'=>array('catalog/products-form')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/products'))),
                    array('layout', 'form'),
                    array('layout', 'catalog_product_form_tabs'),
                ),
                '/catalog/categories'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('catalog/categories')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/categories'))),
                ),
                '/catalog/categories/tree_form'=>array(
                    array('root', 'catalog/categories-tree-form'),
                    array('view', 'catalog/categories-tree-form',
                        'set'=>array(
                            'tab_view_prefix' => 'catalog/categories-tree-form/',
                        ),
                        'do'=>array(
                            array('addTab', 'main', array('label'=>'Category', 'pos'=>10)),
                            array('addTab', 'content', array('label'=>'Page Content', 'pos'=>20)),
                            array('addTab', 'products', array('label'=>'Products', 'pos'=>30, 'async'=>true)),
                            //array('addTab', 'main', array('label'=>'History', 'pos'=>100)),
                        ),
                    ),
                ),
                '/catalog/families'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('catalog/families')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/families'))),
                ),
                '/settings'=>array(
                    array('view', 'settings', 'do'=>array(
                        array('addTab', 'FCom_Catalog', array('label'=>'Catalog', 'async'=>true)),
                    )),
                ),
            ));
        ;
    }

    public static function onProductsEditPost($args)
    {
print_r($args); exit;
    }

    public static function onNavTreeForm($args)
    {
        $args['node_types']['category'] = 'Category';
    }
}