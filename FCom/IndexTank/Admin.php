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

        if( BConfig::i()->get('modules/FCom_IndexTank/api_url') ){

            if(0 == BConfig::i()->get('modules/FCom_IndexTank/disable_auto_indexing') ){
                BEvents::i()->on('FCom_Catalog_Model_Product::onAfterSave', 'FCom_IndexTank_Admin::onProductAfterSave')
                    ->on('FCom_Catalog_Model_Product::onBeforeDelete', 'FCom_IndexTank_Admin::onProductBeforeDelete')

                    //for categories
                    ->on('FCom_Catalog_Admin_Controller_Categories::action_tree_data__POST:move_node:before', 'FCom_IndexTank_Admin::onCategoryMoveBefore')
                    ->on('FCom_Catalog_Admin_Controller_Categories::action_tree_data__POST:move_node:after', 'FCom_IndexTank_Admin::onCategoryMoveAfter')
                    ->on('FCom_Catalog_Model_Category::onBeforeDelete', 'FCom_IndexTank_Admin::onCategoryBeforeDelete')
                    ->on('FCom_Catalog_Model_CategoryProduct::onAfterSave', 'FCom_IndexTank_Admin::onCategoryProductAfterSave')
                    ->on('FCom_Catalog_Model_CategoryProduct::onBeforeDelete', 'FCom_IndexTank_Admin::onCategoryProductBeforeDelete')
                    //for custom fields
                    ->on('FCom_CustomField_Model_Field::onAfterSave', 'FCom_IndexTank_Admin::onCustomFieldAfterSave')
                    ->on('FCom_CustomField_Model_Field::onBeforeDelete', 'FCom_IndexTank_Admin::onCustomFieldBeforeDelete')
                ;
            }


        }

        FCom_Admin_Model_Role::i()->createPermission(array(
            'index_tank' => 'Index Tank',
            'index_tank/product_field' => 'Product Fields',
            'index_tank/product_function' => 'Product Functions',
        ));

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
    static public function productsIndexStart()
    {
        FCom_Catalog_Model_Product::i()->update_many(array('indextank_indexed' => '0'), "indextank_indexed != 0");

        FCom_IndexTank_Model_IndexingStatus::i()->updateInfoStatus();
    }

    static public function productsIndexPause()
    {
        FCom_IndexTank_Model_IndexingStatus::i()->setIndexingStatus('pause');
    }

    static public function productsIndexResume()
    {
        FCom_IndexTank_Model_IndexingStatus::i()->setIndexingStatus('start');
    }

    /**
     * Mark all product for re-index
     */
    static public function productsIndexingStatus()
    {
        // disable caching
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: Mon, 26 Jul 1991 05:00:00 GMT');  // disable IE caching
        header('Content-Type: text/plain; charset=utf-8');

        $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->getIndexingStatus();
        $res = array(
            'index_size' => $indexingStatus->index_size,
            'to_index' => $indexingStatus->to_index,
            'percent' => ceil($indexingStatus->percent),
            'status' => $indexingStatus->status
                );
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
        FCom_IndexTank_Cron::i()->setProductsStatus(0, $product);
    }

    /**
     * Catch event FCom_Catalog_Model_Product::BeforeDelete
     * to delete given product from index
     * @param array $args contain product model
     */
    static public function onProductBeforeDelete($args)
    {
        $product = $args['model'];
        FCom_IndexTank_Cron::i()->setProductsStatus(0, $product);
    }


    /**
     *Catch move category
     * @param type $args
     */
    static public function onCategoryMoveAfter($args)
    {
        if (empty($args['id'])) {
            return;
        }
        $categoryMoving = FCom_Catalog_Model_Category::i()->load($args['id']);
        $catIds = explode("/", $categoryMoving->id_path);

        if (empty($catIds)) {
            return;
        }
        $categories = FCom_Catalog_Model_Category::i()->orm()->where_in('id', $catIds)->find_many_assoc();
        foreach($categories as $category) {
            $products = $category->products();
            if (!$products) {
                continue;
            }
            FCom_IndexTank_Cron::i()->setProductsStatus(0, $products);
        }
    }
    static public function onCategoryMoveBefore($args)
    {
        if (empty($args['id'])) {
            return;
        }
        $categoryMoving = FCom_Catalog_Model_Category::i()->load($args['id']);
        $catIds = explode("/", $categoryMoving->id_path);

        if (empty($catIds)) {
            return;
        }
        $categories = FCom_Catalog_Model_Category::i()->orm()->where_in('id', $catIds)->find_many_assoc();
        foreach($categories as $category) {
            $products = $category->products();
            if (!$products) {
                continue;
            }
            foreach ($products as $product) {
                //delete source categories for products
                FCom_IndexTank_Index_Product::i()->deleteCategories($product, $category);
            }
            FCom_IndexTank_Cron::i()->setProductsStatus(0, $products);
        }
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
        if (!$products) {
            return;
        }
        FCom_IndexTank_Cron::i()->setProductsStatus(0, $products);
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
        if (!$products) {
            return;
        }
        FCom_IndexTank_Cron::i()->setProductsStatus(0, $products);
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
}
