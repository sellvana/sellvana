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
            ->route('GET /api/products', 'FCom_Catalog_Admin_Controller.products')
            ->route('GET /api/category_tree', 'FCom_Catalog_Admin_Controller.category_tree_get')
            ->route('POST /api/category_tree', 'FCom_Catalog_Admin_Controller.category_tree_post')
        ;

        BLayout::i()
            ->allViews('Admin/views', 'catalog/')
            ->view('grid', array('view_class'=>'BViewGrid'))
        ;

        BPubSub::i()
            ->on('category_tree_post.associate.products', 'FCom_Catalog_Model_Product.onAssociateCategory')
            ->on('category_tree_post.reorderAZ', 'FCom_Catalog_Model_Category.onReorderAZ')
        ;
    }

}