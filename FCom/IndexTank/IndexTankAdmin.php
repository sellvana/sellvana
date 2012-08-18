<?php

class FCom_IndexTank_Admin extends BClass
{
    /**
     * Bootstrap IndexTank routes, events and layout for Admin part
     */
    static public function bootstrap()
    {
        $module = BApp::m();
        $module->base_src .= '/Admin';

        BFrontController::i()
            ->route('GET /indextank/product_fields', 'FCom_IndexTank_Admin_Controller_ProductFields.index')
            ->route('GET|POST /indextank/product_fields/.action', 'FCom_IndexTank_Admin_Controller_ProductFields')

            ->route('GET /indextank/product_functions', 'FCom_IndexTank_Admin_Controller_ProductFunctions.index')
            ->route('GET|POST /indextank/product_functions/.action', 'FCom_IndexTank_Admin_Controller_ProductFunctions')

            //api function
            ->route('GET /indextank/products/index', 'FCom_IndexTank_Admin::productsIndexAll')
            ->route('GET /indextank/products/indexing-status', 'FCom_IndexTank_Admin::productsIndexingStatus')
            //->route('GET /indextank/products/index-stop', 'FCom_IndexTank_Admin::productsStopIndexAll')
            ->route('DELETE /indextank/products/index', 'FCom_IndexTank_Admin::productsDeleteAll');

        BLayout::i()->addAllViews('Admin/views');

        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_IndexTank_Admin::layout');

        if( BConfig::i()->get('modules/FCom_IndexTank/api_url') ){
            if(0 == BConfig::i()->get('modules/FCom_IndexTank/disable_auto_indexing') ){
                BPubSub::i()->on('FCom_Catalog_Model_Product::afterSave', 'FCom_IndexTank_Admin::onProductAfterSave')
                        ->on('FCom_Catalog_Model_Product::beforeDelete', 'FCom_IndexTank_Admin::onProductBeforeDelete')

                        //for categories
                        ->on('FCom_Catalog_Admin_Controller_Categories::::action_tree_data__POST.move_node', 'FCom_IndexTank_Admin::onCategoryMove')
                        ->on('FCom_Catalog_Model_Category::beforeDelete', 'FCom_IndexTank_Admin::onCategoryBeforeDelete')
                        ->on('FCom_Catalog_Model_CategoryProduct::afterSave', 'FCom_IndexTank_Admin::onCategoryProductAfterSave')
                        ->on('FCom_Catalog_Model_CategoryProduct::beforeDelete', 'FCom_IndexTank_Admin::onCategoryProductBeforeDelete')
                        //for custom fields
                        ->on('FCom_CustomField_Model_Field::afterSave', 'FCom_IndexTank_Admin::onCustomFieldAfterSave')
                        ->on('FCom_CustomField_Model_Field::beforeDelete', 'FCom_IndexTank_Admin::onCustomFieldBeforeDelete')
                ;
            }
            //for API init
            BPubSub::i()->on('FCom_Admin_Controller_Settings::action_index__POST', 'FCom_IndexTank_Admin::onSaveAdminSettings');
        }
        FCom_IndexTank_Admin_Controller::bootstrap();
    }

    static public function onSaveAdminSettings($post)
    {
        if (empty($post['post']['config']['modules']['FCom_IndexTank']['api_url'])) {
            return false;
        }
        $apiUrl = $post['post']['config']['modules']['FCom_IndexTank']['api_url'];

        BConfig::i()->set('modules/FCom_IndexTank/api_url', $apiUrl);

        //create product index
        FCom_IndexTank_Index_Product::i()->install();

        //insert predefined functions
        $functionsList = FCom_IndexTank_Model_ProductFunction::i()->getList();
        foreach ($functionsList as $func) {
            FCom_IndexTank_Index_Product::i()->updateFunction($func->number, $func->definition);
        }
    }

    /**
     * Delete all indexed products
     */
    static public function productsDeleteAll()
    {
        FCom_IndexTank_Index_Product::i()->dropIndex();
        FCom_IndexTank_Index_Product::i()->createIndex();
    }

    /**
     * Mark all product for re-index
     */
    static public function productsIndexAll()
    {
        FCom_Catalog_Model_Product::i()->update_many(array('indextank_indexed' => '0'), "1");
    }

    /**
     * Mark all product for re-index
     */
    static public function productsIndexingStatus()
    {
        $countNotIndexed = FCom_Catalog_Model_Product::orm()->where('indextank_indexed', 0)->count();
        $countTotal = FCom_Catalog_Model_Product::orm()->count();

        // disable caching
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: Mon, 26 Jul 1991 05:00:00 GMT');  // disable IE caching
        header('Content-Type: text/plain; charset=utf-8');

        $percent =  (($countTotal - $countNotIndexed)/$countTotal)*100;
        $res = array('indexed' => $countTotal - $countNotIndexed, 'percent' => ceil($percent));
        echo BUtil::toJson($res);
        exit;
    }

    /**
     * Catch event FCom_Catalog_Model_Product::afterSave
     * to reindex given product
     * @param array $args contain product model
     */
    static public function onProductAfterSave($args)
    {
        $product = $args['model'];
        FCom_IndexTank_Index_Product::i()->add($product);
    }

    /**
     * Catch event FCom_Catalog_Model_Product::BeforeDelete
     * to delete given product from index
     * @param array $args contain product model
     */
    static public function onProductBeforeDelete($args)
    {
        $product = $args['model'];
        FCom_IndexTank_Index_Product::i()->deleteProducts($product);
    }


    /**
     *Catch move category
     * @param type $args
     */

    static public function onCategoryMove($args)
    {
        $category = $args['model'];
        $products = $category->products();
        $productIds = array();
        foreach ($products as $product) {
            $productIds[] = $product->id();
        }
        FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 0),
                    "id in (".implode(",", $productIds).")");
    }

    static public function onCategoryProductAfterSave($args)
    {
        $cp = $args['model'];
        $product = FCom_Catalog_Model_Product::i()->load($cp->product_id);
        FCom_IndexTank_Index_Product::i()->updateCategories($product);
    }


    /**
     * Catch event FCom_Catalog_Model_Category::BeforeDelete
     * to delete given category from products index
     * @param array $args contain category model
     */
    static public function onCategoryBeforeDelete($args)
    {
        $category = $args['model'];
        $products = $category->products();
        $productIds = array();
        foreach ($products as $product) {
            $productIds[] = $product->id();
        }
        FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 0),
                    "id in (".implode(",", $productIds).")");
    }

    static public function onCategoryProductBeforeDelete($args)
    {
        $cp = $args['model'];
        $product = FCom_Catalog_Model_Product::i()->load($cp->product_id);
        $category = FCom_Catalog_Model_Category::i()->load($cp->category_id);
        FCom_IndexTank_Index_Product::i()->deleteCategories($product, $category);
    }

    /**
     * Catch event FCom_CustomField_Model_Field::afterSave
     * to update given custom field in products index
     * @param array $args contain custom field model
     */
    static public function onCustomFieldAfterSave($args)
    {
        $cfModel = $args['model'];
        //add custom field to the IndexTank product field table if not exists yet
        $fieldName = FCom_IndexTank_Index_Product::i()->getCustomFieldKey($cfModel);
        $doc = FCom_IndexTank_Model_ProductField::orm()->where('field_name', $fieldName)->find_one();
        if (!$doc) {
            $doc = FCom_IndexTank_Model_ProductField::orm()->create();
            $matches = array();
            preg_match("#(\w+)#", $cfModel->table_field_type, $matches);
            $type = $matches[1];

            $doc->field_name        = $fieldName;
            $doc->field_nice_name   = $cfModel->frontend_label;
            $doc->field_type        = $type;
            $doc->facets            = 0;
            $doc->search            = 0;
            $doc->source_type       = 'product';
            $doc->source_value      = $cfModel->field_code;

            $doc->save();
        } elseif ('product' == $doc->source_type && $doc->source_value != $cfModel->field_code) {
            $doc->source_value      = $cfModel->field_code;
            $doc->save();
        }

        $products = $cfModel->products();
        foreach ($products as $product) {
            FCom_IndexTank_Index_Product::i()->updateCategories($product);
        }
    }

    /**
     * Catch event FCom_CustomField_Model_Field::BeforeDelete
     * to delete given custom field from products index
     * @param array $args contain custom field model
     */
    static public function onCustomFieldBeforeDelete($args)
    {
        $cfModel = $args['model'];
        $fieldName = FCom_IndexTank_Index_Product::i()->getCustomFieldKey($cfModel);
        $doc = FCom_IndexTank_Model_ProductField::orm()->where('field_name', $fieldName)->find_one();
        if (!$doc) {
            return;
        }
        $products = $cfModel->products();
        if (!$products) {
            return;
        }
        if ($doc->search) {
            FCom_IndexTank_Index_Product::i()->updateTextField($products, $fieldName, '');
        }
        if ($doc->facets) {
            foreach($products as $product){
                FCom_IndexTank_Index_Product::i()->deleteCategory($product, $fieldName);
            }
        }
        $doc->delete();

    }


    /**
     * Itialized base layout, navigation links and page views scripts
     */
    static public function layout()
    {
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'admin/header', 'do'=>array(
                        array('addNav', 'indextank', array('label'=>'IndexDen', 'pos'=>100)),
                        array('addNav', 'indextank/product_fields', array('label'=>'Product fields', 'href'=>BApp::href('indextank/product_fields'))),
                        array('addNav', 'indextank/product_functions', array('label'=>'Product functions', 'href'=>BApp::href('indextank/product_functions'))),
                    ))),
                '/indextank/product_fields'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('indextank/product_fields')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'indextank/product_fields'))),
                ),
                '/indextank/product_fields/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('admin/form')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'indextank/product_fields'))),
                    array('view', 'admin/form', 'set'=>array(
                        'tab_view_prefix' => 'indextank/product_fields-form/',
                    ), 'do'=>array(
                        array('addTab', 'main', array('label'=>'Product Fields', 'pos'=>10))
                    )),
                ),
                '/indextank/product_functions'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('indextank/product_functions')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'indextank/product_functions'))),
                ),
                '/indextank/product_functions/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('admin/form')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'indextank/product_functions'))),
                    array('view', 'admin/form', 'set'=>array(
                        'tab_view_prefix' => 'indextank/product_functions-form/',
                    ), 'do'=>array(
                        array('addTab', 'main', array('label'=>'Product Functions', 'pos'=>10))
                    )),
                ),
                '/settings'=>array(
                    array('view', 'settings', 'do'=>array(
                        array('addTab', 'FCom_IndexTank', array('label'=>'IndexDen API', 'async'=>true))
                    )))

            ));
    }


}