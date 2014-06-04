<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_IndexTank_Admin extends BClass
{
    /**
     * Bootstrap IndexTank routes, events and layout for Admin part
     */
    public function bootstrap()
    {
        $module = $this->BApp->m();
        $module->base_src .= '/Admin';

        if ($this->BConfig->get('modules/FCom_IndexTank/api_url')) {

            if (0 == $this->BConfig->get('modules/FCom_IndexTank/disable_auto_indexing')) {
                $this->BEvents->on('FCom_Catalog_Model_Product::onAfterSave', 'FCom_IndexTank_Admin::onProductAfterSave')
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

        $this->FCom_Admin_Model_Role->createPermission([
            'index_tank' => 'Index Tank',
            'index_tank/product_field' => 'Product Fields',
            'index_tank/product_function' => 'Product Functions',
        ]);

        $this->FCom_IndexTank_Admin_Controller->bootstrap();
    }

    public function onSaveAdminSettings($post)
    {

        if (empty($post['post']['config']['modules']['FCom_IndexTank']['api_url'])) {
            return false;
        }
        $apiUrl = $post['post']['config']['modules']['FCom_IndexTank']['api_url'];

        $this->BConfig->set('modules/FCom_IndexTank/api_url', $apiUrl);

        //create product index
        $this->FCom_IndexTank_Index_Product->install();

        //insert predefined functions
        $functionsList = $this->FCom_IndexTank_Model_ProductFunction->getList();
        foreach ($functionsList as $func) {
            $this->FCom_IndexTank_Index_Product->updateFunction($func->number, $func->definition);
        }
    }

    /**
     * Delete all indexed products
     */
    public function productsDeleteAll()
    {
        $this->FCom_IndexTank_Index_Product->dropIndex();
        $this->FCom_IndexTank_Index_Product->createIndex();
    }

    /**
     * Mark all product for re-index
     */
    public function productsIndexStart()
    {
        $this->FCom_Catalog_Model_Product->update_many(['indextank_indexed' => '0'], "indextank_indexed != 0");

        $this->FCom_IndexTank_Model_IndexingStatus->updateInfoStatus();
    }

    public function productsIndexPause()
    {
        $this->FCom_IndexTank_Model_IndexingStatus->setIndexingStatus('pause');
    }

    public function productsIndexResume()
    {
        $this->FCom_IndexTank_Model_IndexingStatus->setIndexingStatus('start');
    }

    /**
     * Mark all product for re-index
     */
    public function productsIndexingStatus()
    {
        // disable caching
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: Mon, 26 Jul 1991 05:00:00 GMT');  // disable IE caching
        header('Content-Type: text/plain; charset=utf-8');

        $indexingStatus = $this->FCom_IndexTank_Model_IndexingStatus->getIndexingStatus();
        $res = [
            'index_size' => $indexingStatus->index_size,
            'to_index' => $indexingStatus->to_index,
            'percent' => ceil($indexingStatus->percent),
            'status' => $indexingStatus->status
                ];
        echo $this->BUtil->toJson($res);
        exit;
    }

    /**
     * Catch event FCom_Catalog_Model_Product::afterSave
     * to reindex given product
     * @param array $args contain product model
     */
    public function onProductAfterSave($args)
    {
        $product = $args['model'];
        $this->FCom_IndexTank_Cron->setProductsStatus(0, $product);
    }

    /**
     * Catch event FCom_Catalog_Model_Product::BeforeDelete
     * to delete given product from index
     * @param array $args contain product model
     */
    public function onProductBeforeDelete($args)
    {
        $product = $args['model'];
        $this->FCom_IndexTank_Cron->setProductsStatus(0, $product);
    }


    /**
     *Catch move category
     * @param type $args
     */
    public function onCategoryMoveAfter($args)
    {
        if (empty($args['id'])) {
            return;
        }
        $categoryMoving = $this->FCom_Catalog_Model_Category->load($args['id']);
        $catIds = explode("/", $categoryMoving->id_path);

        if (empty($catIds)) {
            return;
        }
        $categories = $this->FCom_Catalog_Model_Category->orm()->where_in('id', $catIds)->find_many_assoc();
        foreach ($categories as $category) {
            $products = $category->products();
            if (!$products) {
                continue;
            }
            $this->FCom_IndexTank_Cron->setProductsStatus(0, $products);
        }
    }
    public function onCategoryMoveBefore($args)
    {
        if (empty($args['id'])) {
            return;
        }
        $categoryMoving = $this->FCom_Catalog_Model_Category->load($args['id']);
        $catIds = explode("/", $categoryMoving->id_path);

        if (empty($catIds)) {
            return;
        }
        $categories = $this->FCom_Catalog_Model_Category->orm()->where_in('id', $catIds)->find_many_assoc();
        foreach ($categories as $category) {
            $products = $category->products();
            if (!$products) {
                continue;
            }
            foreach ($products as $product) {
                //delete source categories for products
                $this->FCom_IndexTank_Index_Product->deleteCategories($product, $category);
            }
            $this->FCom_IndexTank_Cron->setProductsStatus(0, $products);
        }
    }

    public function onCategoryProductAfterSave($args)
    {
        $cp = $args['model'];
        $product = $this->FCom_Catalog_Model_Product->load($cp->product_id);
        $this->FCom_IndexTank_Index_Product->updateCategories($product);
    }


    /**
     * Catch event FCom_Catalog_Model_Category::BeforeDelete
     * to delete given category from products index
     * @param array $args contain category model
     */
    public function onCategoryBeforeDelete($args)
    {
        $category = $args['model'];
        $products = $category->products();
        if (!$products) {
            return;
        }
        $this->FCom_IndexTank_Cron->setProductsStatus(0, $products);
    }

    public function onCategoryProductBeforeDelete($args)
    {
        $cp = $args['model'];
        $product = $this->FCom_Catalog_Model_Product->load($cp->product_id);
        $category = $this->FCom_Catalog_Model_Category->load($cp->category_id);
        $this->FCom_IndexTank_Index_Product->deleteCategories($product, $category);
    }

    /**
     * Catch event FCom_CustomField_Model_Field::afterSave
     * to update given custom field in products index
     * @param array $args contain custom field model
     */
    public function onCustomFieldAfterSave($args)
    {
        $cfModel = $args['model'];
        //add custom field to the IndexTank product field table if not exists yet
        $fieldName = $this->FCom_IndexTank_Index_Product->getCustomFieldKey($cfModel);
        $doc = $this->FCom_IndexTank_Model_ProductField->orm()->where('field_name', $fieldName)->find_one();
        if (!$doc) {
            $doc = $this->FCom_IndexTank_Model_ProductField->orm()->create();
            $matches = [];
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
        $this->FCom_IndexTank_Cron->setProductsStatus(0, $products);
    }

    /**
     * Catch event FCom_CustomField_Model_Field::BeforeDelete
     * to delete given custom field from products index
     * @param array $args contain custom field model
     */
    public function onCustomFieldBeforeDelete($args)
    {
        $cfModel = $args['model'];
        $fieldName = $this->FCom_IndexTank_Index_Product->getCustomFieldKey($cfModel);
        $doc = $this->FCom_IndexTank_Model_ProductField->orm()->where('field_name', $fieldName)->find_one();
        if (!$doc) {
            return;
        }
        $products = $cfModel->products();
        if (!$products) {
            return;
        }
        if ($doc->search) {
            $this->FCom_IndexTank_Index_Product->updateTextField($products, $fieldName, '');
        }
        if ($doc->facets) {
            foreach ($products as $product) {
                $this->FCom_IndexTank_Index_Product->deleteCategory($product, $fieldName);
            }
        }
        $doc->delete();
    }
}
