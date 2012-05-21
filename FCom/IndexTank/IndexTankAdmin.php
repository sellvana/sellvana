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

        //    ->route('GET /indextank/dashboard', 'FCom_IndexTank_Admin_Controller.dashboard')

                //api function
            ->route('GET /indextank/products/index', 'FCom_IndexTank_Admin::productsIndexAll')
            ->route('DELETE /indextank/products/index', 'FCom_IndexTank_Admin::productsDeleteAll');


        BLayout::i()->addAllViews('Admin/views');

        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_IndexTank_Admin::layout');
        if( BConfig::i()->get('modules/FCom_IndexTank/api_url') ){
            BPubSub::i()->on('FCom_Catalog_Model_Product::afterSave', 'FCom_IndexTank_Admin::onProductAfterSave')
                    ->on('FCom_Catalog_Model_Product::beforeDelete', 'FCom_IndexTank_Admin::onProductBeforeDelete')

                    //for categories
                    ->on('FCom_Catalog_Model_Category::afterSave', 'FCom_IndexTank_Admin::onCategoryAfterSave')
                    ->on('FCom_Catalog_Model_Category::beforeDelete', 'FCom_IndexTank_Admin::onCategoryBeforeDelete')
                    ->on('FCom_Catalog_Model_CategoryProduct::afterSave', 'FCom_IndexTank_Admin::onCategoryProductAfterSave')
                    ->on('FCom_Catalog_Model_CategoryProduct::beforeDelete', 'FCom_IndexTank_Admin::onCategoryProductBeforeDelete')
                    //for custom fields
                    ->on('FCom_CustomField_Model_Field::afterSave', 'FCom_IndexTank_Admin::onCustomFieldAfterSave')
                    ->on('FCom_CustomField_Model_Field::beforeDelete', 'FCom_IndexTank_Admin::onCustomFieldBeforeDelete')
                    //for API init
                    ->on('FCom_Admin_Controller_Settings::action_index__POST', 'FCom_IndexTank_Admin::onSaveAdminSettings')
            ;
        }
        FCom_IndexTank_Admin_Controller::bootstrap();
    }

    static public function onSaveAdminSettings($post)
    {
        if (empty($post['post']['config']['modules']['FCom_IndexTank']['api_url'])){
            return false;
        }
        $api_url = $post['post']['config']['modules']['FCom_IndexTank']['api_url'];

        BConfig::i()->set('modules/FCom_IndexTank/api_url', $api_url);

        //create product index
        FCom_IndexTank_Index_Product::i()->install();

        //insert predefined functions
        $functions_list = FCom_IndexTank_Model_ProductFunction::i()->get_list();
        foreach($functions_list as $func){
            FCom_IndexTank_Index_Product::i()->update_function($func->number, $func->definition);
        }
    }

    static public function startProductsIndexAll()
    {
        $script = dirname(__DIR__)."/Cron/index_all.php";
        $exclusive = dirname(__DIR__). "/../../exclusive.php";
        $command = "nohup php {$exclusive} indextank_index_all php {$script} &";
        system($command);
    }

    static public function startProductsDeleteAll()
    {
        self::productsDeleteAll();
    }

    /**
     * Delete all indexed products
     */
    static public function productsDeleteAll()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*');
        $limit = 1000;
        $offset = 0;
        $counter = 0;
        $products = $orm->offset($offset)->limit($limit)->find_many();
        while($products) {
            $counter += count($products);

            FCom_IndexTank_Index_Product::i()->delete($products);

            $offset += $limit;
            $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*');
            $products = $orm->offset($offset)->limit($limit)->find_many();
        };

        echo $counter . ' products deleted';
    }

    /**
     * Index all products
     */
    static public function productsIndexAll($debug=false, $batch_size=1000)
    {
        set_time_limit(0);
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*');
        $offset = 0;
        $counter = 0;
        $products = $orm->offset($offset)->limit($batch_size)->find_many();
        while($products) {
            $counter += count($products);
            FCom_IndexTank_Index_Product::i()->add($products, $batch_size);

            $offset += $batch_size;
            $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*');
            $products = $orm->offset($offset)->limit($batch_size)->find_many();
            if($debug){
                echo "Indexed: $counter\n";
            }
        };

        echo $counter . ' products indexed';
    }
    static public function productIndexDropField($field)
    {
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*');
        $limit = 1000;
        $offset = 0;
        $counter = 0;
        $products = $orm->offset($offset)->limit($limit)->find_many();
        while($products) {
            $counter += count($products);
            FCom_IndexTank_Index_Product::i()->updateTextField($products, $field, '');

            $offset += $limit;
            $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*');
            $products = $orm->offset($offset)->limit($limit)->find_many();
        };
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
        FCom_IndexTank_Index_Product::i()->delete($product);
    }


    /**
     * Catch event FCom_Catalog_Model_Category::afterSave
     * to update given category in products index
     * @param array $args contain category model
     */
    static public function onCategoryAfterSave($args)
    {
        $category = $args['model'];
        $products = $category->products();
        foreach($products as $product){
            FCom_IndexTank_Index_Product::i()->update_categories($product);
        }
    }

    static public function onCategoryProductAfterSave($args)
    {
        $cp = $args['model'];
        $product = FCom_Catalog_Model_Product::i()->load($cp->product_id);
        FCom_IndexTank_Index_Product::i()->update_categories($product);
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
        foreach($products as $product){
            FCom_IndexTank_Index_Product::i()->delete_categories($product, $category);
        }
    }

    static public function onCategoryProductBeforeDelete($args)
    {
        $cp = $args['model'];
        $product = FCom_Catalog_Model_Product::i()->load($cp->product_id);
        $category = FCom_Catalog_Model_Category::i()->load($cp->category_id);
        FCom_IndexTank_Index_Product::i()->delete_categories($product, $category);
    }

    /**
     * Catch event FCom_CustomField_Model_Field::afterSave
     * to update given custom field in products index
     * @param array $args contain custom field model
     */
    static public function onCustomFieldAfterSave($args)
    {
        $cf_model = $args['model'];
        //add custom field to the IndexTank product field table if not exists yet
        $field_name = FCom_IndexTank_Index_Product::i()->get_custom_field_key($cf_model);
        $doc = FCom_IndexTank_Model_ProductField::orm()->where('field_name', $field_name)->find_one();
        if (!$doc){
            $doc = FCom_IndexTank_Model_ProductField::orm()->create();
            $matches = array();
            preg_match("#(\w+)#", $cf_model->table_field_type, $matches);
            $type = $matches[1];

            $doc->field_name        = $field_name;
            $doc->field_nice_name   = $cf_model->frontend_label;
            $doc->field_type        = $type;
            $doc->facets            = 0;
            $doc->search            = 0;
            $doc->source_type       = 'product';
            $doc->source_value      = $cf_model->field_code;

            $doc->save();
        } elseif('product' == $doc->source_type && $doc->source_value != $cf_model->field_code) {
            $doc->source_value      = $cf_model->field_code;
            $doc->save();
        }

        $products = $cf_model->products();
        foreach($products as $product){
            FCom_IndexTank_Index_Product::i()->update_categories($product);
        }
    }

    /**
     * Catch event FCom_CustomField_Model_Field::BeforeDelete
     * to delete given custom field from products index
     * @param array $args contain custom field model
     */
    static public function onCustomFieldBeforeDelete($args)
    {
        $cf_model = $args['model'];
        $field_name = FCom_IndexTank_Index_Product::i()->get_custom_field_key($cf_model);
        $doc = FCom_IndexTank_Model_ProductField::orm()->where('field_name', $field_name)->find_one();
        if (!$doc){
            return;
        }
        if($doc->search){
            self::productIndexDropField($field_name);
        }
        if($doc->facets){
            $products = $cf_model->products();
            foreach($products as $product){
                FCom_IndexTank_Index_Product::i()->delete_category($product, $field_name);
            }
        }
        $doc->delete();

    }


    /**
     * Itialized base layout, navigation links and page views scripts
     */
    static public function layout()
    {
        $baseHref = BApp::href('indextank');
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'admin/header', 'do'=>array(
                        array('addNav', 'indextank', array('label'=>'IndexDen', 'pos'=>100)),
//                        array('addNav', 'indextank/dashboard', array('label'=>'Dashboard', 'pos'=>100, 'href'=>$baseHref.'/dashboard')),
                        array('addNav', 'indextank/product_fields', array('label'=>'Product fields', 'href'=>BApp::href('indextank/product_fields'))),
                        array('addNav', 'indextank/product_functions', array('label'=>'Product functions', 'href'=>BApp::href('indextank/product_functions'))),
                    ))),
       /*         '/indextank/dashboard'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('indextank/dashboard')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'indextank/dashboard'))),
                ),*/
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
                        array('addTab', 'main', array('label'=>'Product Fields', 'pos'=>10)),
                        array('addTab', 'display', array('label'=>'Display options', 'pos'=>15))
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