<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_IndexTank_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property Sellvana_IndexTank_Admin_Controller $Sellvana_IndexTank_Admin_Controller
 * @property Sellvana_IndexTank_Index_Product $Sellvana_IndexTank_Index_Product
 * @property Sellvana_IndexTank_Model_ProductFunction $Sellvana_IndexTank_Model_ProductFunction
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_IndexTank_Model_IndexingStatus $Sellvana_IndexTank_Model_IndexingStatus
 * @property Sellvana_IndexTank_Cron $Sellvana_IndexTank_Cron
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_IndexTank_Model_ProductField $Sellvana_IndexTank_Model_ProductField
 */
class Sellvana_IndexTank_Admin extends BClass
{
    /**
     * Bootstrap IndexTank routes, events and layout for Admin part
     */
    public function bootstrap()
    {
        $module = $this->BApp->m();
        $module->base_src .= '/Admin';

        if ($this->BConfig->get('modules/Sellvana_IndexTank/api_url')) {

            if (0 == $this->BConfig->get('modules/Sellvana_IndexTank/disable_auto_indexing')) {
                $this->BEvents->on('Sellvana_Catalog_Model_Product::onAfterSave', 'Sellvana_IndexTank_Admin::onProductAfterSave')
                    ->on('Sellvana_Catalog_Model_Product::onBeforeDelete', 'Sellvana_IndexTank_Admin::onProductBeforeDelete')

                    //for categories
                    ->on('Sellvana_Catalog_Admin_Controller_Categories::action_tree_data__POST:move_node:before', 'Sellvana_IndexTank_Admin::onCategoryMoveBefore')
                    ->on('Sellvana_Catalog_Admin_Controller_Categories::action_tree_data__POST:move_node:after', 'Sellvana_IndexTank_Admin::onCategoryMoveAfter')
                    ->on('Sellvana_Catalog_Model_Category::onBeforeDelete', 'Sellvana_IndexTank_Admin::onCategoryBeforeDelete')
                    ->on('Sellvana_Catalog_Model_CategoryProduct::onAfterSave', 'Sellvana_IndexTank_Admin::onCategoryProductAfterSave')
                    ->on('Sellvana_Catalog_Model_CategoryProduct::onBeforeDelete', 'Sellvana_IndexTank_Admin::onCategoryProductBeforeDelete')
                    //for custom fields
                    ->on('Sellvana_CustomField_Model_Field::onAfterSave', 'Sellvana_IndexTank_Admin::onCustomFieldAfterSave')
                    ->on('Sellvana_CustomField_Model_Field::onBeforeDelete', 'Sellvana_IndexTank_Admin::onCustomFieldBeforeDelete')
                ;
            }


        }

        $this->FCom_Admin_Model_Role->createPermission([
            'index_tank' => 'Index Tank',
            'index_tank/product_field' => 'Product Fields',
            'index_tank/product_function' => 'Product Functions',
        ]);

        $this->Sellvana_IndexTank_Admin_Controller->bootstrap();
    }

    public function onSaveAdminSettings($post)
    {

        if (empty($post['post']['config']['modules']['Sellvana_IndexTank']['api_url'])) {
            return false;
        }
        $apiUrl = $post['post']['config']['modules']['Sellvana_IndexTank']['api_url'];

        $this->BConfig->set('modules/Sellvana_IndexTank/api_url', $apiUrl);

        //create product index
        $this->Sellvana_IndexTank_Index_Product->install();

        //insert predefined functions
        $functionsList = $this->Sellvana_IndexTank_Model_ProductFunction->getList();
        foreach ($functionsList as $func) {
            $this->Sellvana_IndexTank_Index_Product->updateFunction($func->number, $func->definition);
        }
    }

    /**
     * Delete all indexed products
     */
    public function productsDeleteAll()
    {
        $this->Sellvana_IndexTank_Index_Product->dropIndex();
        $this->Sellvana_IndexTank_Index_Product->createIndex();
    }

    /**
     * Mark all product for re-index
     */
    public function productsIndexStart()
    {
        $this->Sellvana_Catalog_Model_Product->update_many(['indextank_indexed' => '0'], "indextank_indexed != 0");

        $this->Sellvana_IndexTank_Model_IndexingStatus->updateInfoStatus();
    }

    public function productsIndexPause()
    {
        $this->Sellvana_IndexTank_Model_IndexingStatus->setIndexingStatus('pause');
    }

    public function productsIndexResume()
    {
        $this->Sellvana_IndexTank_Model_IndexingStatus->setIndexingStatus('start');
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

        $indexingStatus = $this->Sellvana_IndexTank_Model_IndexingStatus->getIndexingStatus();
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
     * Catch event Sellvana_Catalog_Model_Product::afterSave
     * to reindex given product
     * @param array $args contain product model
     */
    public function onProductAfterSave($args)
    {
        $product = $args['model'];
        $this->Sellvana_IndexTank_Cron->setProductsStatus(0, $product);
    }

    /**
     * Catch event Sellvana_Catalog_Model_Product::BeforeDelete
     * to delete given product from index
     * @param array $args contain product model
     */
    public function onProductBeforeDelete($args)
    {
        $product = $args['model'];
        $this->Sellvana_IndexTank_Cron->setProductsStatus(0, $product);
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
        $categoryMoving = $this->Sellvana_Catalog_Model_Category->load($args['id']);
        $catIds = explode("/", $categoryMoving->id_path);

        if (empty($catIds)) {
            return;
        }
        /** @var Sellvana_Catalog_Model_Category[] $categories */
        $categories = $this->Sellvana_Catalog_Model_Category->orm()->where_in('id', $catIds)->find_many_assoc();
        foreach ($categories as $category) {
            $products = $category->products();
            if (!$products) {
                continue;
            }
            $this->Sellvana_IndexTank_Cron->setProductsStatus(0, $products);
        }
    }

    /**
     * @param $args
     */
    public function onCategoryMoveBefore($args)
    {
        if (empty($args['id'])) {
            return;
        }
        $categoryMoving = $this->Sellvana_Catalog_Model_Category->load($args['id']);
        $catIds = explode("/", $categoryMoving->id_path);

        if (empty($catIds)) {
            return;
        }
        /** @var Sellvana_Catalog_Model_Category[] $categories */
        $categories = $this->Sellvana_Catalog_Model_Category->orm()->where_in('id', $catIds)->find_many_assoc();
        foreach ($categories as $category) {
            $products = $category->products();
            if (!$products) {
                continue;
            }
            foreach ($products as $product) {
                //delete source categories for products
                $this->Sellvana_IndexTank_Index_Product->deleteCategories($product, $category);
            }
            $this->Sellvana_IndexTank_Cron->setProductsStatus(0, $products);
        }
    }

    public function onCategoryProductAfterSave($args)
    {
        $cp = $args['model'];
        $product = $this->Sellvana_Catalog_Model_Product->load($cp->product_id);
        $this->Sellvana_IndexTank_Index_Product->updateCategories($product);
    }


    /**
     * Catch event Sellvana_Catalog_Model_Category::BeforeDelete
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
        $this->Sellvana_IndexTank_Cron->setProductsStatus(0, $products);
    }

    public function onCategoryProductBeforeDelete($args)
    {
        $cp = $args['model'];
        $product = $this->Sellvana_Catalog_Model_Product->load($cp->product_id);
        $category = $this->Sellvana_Catalog_Model_Category->load($cp->category_id);
        $this->Sellvana_IndexTank_Index_Product->deleteCategories($product, $category);
    }

    /**
     * Catch event Sellvana_CustomField_Model_Field::afterSave
     * to update given custom field in products index
     * @param array $args contain custom field model
     */
    public function onCustomFieldAfterSave($args)
    {
        $cfModel = $args['model'];
        //add custom field to the IndexTank product field table if not exists yet
        $fieldName = $this->Sellvana_IndexTank_Index_Product->getCustomFieldKey($cfModel);
        /** @var Sellvana_IndexTank_Model_ProductField $doc */
        $doc = $this->Sellvana_IndexTank_Model_ProductField->orm()->where('field_name', $fieldName)->find_one();
        if (!$doc) {
            $doc = $this->Sellvana_IndexTank_Model_ProductField->create();
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
        $this->Sellvana_IndexTank_Cron->setProductsStatus(0, $products);
    }

    /**
     * Catch event Sellvana_CustomField_Model_Field::BeforeDelete
     * to delete given custom field from products index
     * @param array $args contain custom field model
     */
    public function onCustomFieldBeforeDelete($args)
    {
        $cfModel = $args['model'];
        $fieldName = $this->Sellvana_IndexTank_Index_Product->getCustomFieldKey($cfModel);
        /** @var Sellvana_IndexTank_Model_ProductField $doc */
        $doc = $this->Sellvana_IndexTank_Model_ProductField->orm()->where('field_name', $fieldName)->find_one();
        if (!$doc) {
            return;
        }
        $products = $cfModel->products();
        if (!$products) {
            return;
        }
        if ($doc->search) {
            $this->Sellvana_IndexTank_Index_Product->updateTextField($products, $fieldName, '');
        }
        if ($doc->facets) {
            foreach ($products as $product) {
                $this->Sellvana_IndexTank_Index_Product->deleteCategory($product, $fieldName);
            }
        }
        $doc->delete();
    }
}
