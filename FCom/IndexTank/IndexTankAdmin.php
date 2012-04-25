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

        self::_initButtonsOnProductsPage();

        BFrontController::i()
            ->route('GET /indextank/product_fields', 'FCom_IndexTank_Admin_Controller_ProductFields.index')
            ->route('GET|POST /indextank/product_fields/.action', 'FCom_IndexTank_Admin_Controller_ProductFields')

        //    ->route('GET /indextank/dashboard', 'FCom_IndexTank_Admin_Controller.dashboard')

                //api function
            ->route('GET /indextank/products/index', 'FCom_IndexTank_Admin::productsIndexAll')
            ->route('DELETE /indextank/products/index', 'FCom_IndexTank_Admin::productsDeleteAll');


        BLayout::i()->addAllViews('Admin/views');

        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_IndexTank_Admin::layout')
                    ->on('FCom_Catalog_Model_Product::afterSave', 'FCom_IndexTank_Admin::onProductAfterSave')
                    ->on('FCom_Catalog_Model_Product::beforeDelete', 'FCom_IndexTank_Admin::onProductBeforeDelete')

                    //for categories
                    ->on('FCom_Catalog_Model_Category::afterSave', 'FCom_IndexTank_Admin::onCategoryAfterSave')
                    ->on('FCom_Catalog_Model_Category::beforeDelete', 'FCom_IndexTank_Admin::onCategoryBeforeDelete')
                    //for custom fields
                    ->on('FCom_CustomField_Model_Field::afterSave', 'FCom_IndexTank_Admin::onCustomFieldAfterSave')
                    ->on('FCom_CustomField_Model_Field::beforeDelete', 'FCom_IndexTank_Admin::onCustomFieldBeforeDelete')
            ;
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
    static public function productsIndexAll()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*');
        $limit = 1000;
        $offset = 0;
        $counter = 0;
        $products = $orm->offset($offset)->limit($limit)->find_many();
        while($products) {
            $counter += count($products);
            FCom_IndexTank_Index_Product::i()->add($products);

            $offset += $limit;
            $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*');
            $products = $orm->offset($offset)->limit($limit)->find_many();
        };

        echo $counter . ' products indexed';
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

    /**
     * Catch event FCom_CustomField_Model_Field::afterSave
     * to update given custom field in products index
     * @param array $args contain custom field model
     */
    static public function onCustomFieldAfterSave($args)
    {
        $cf_model = $args['model'];
        $products = $cf_model->products();
        foreach($products as $product){
            FCom_IndexTank_Index_Product::i()->update_categories($product);
        }
    }

    /**
     *Update custom field name before save
     * @param array $args
     * @return void
     */
    static public function onCustomFieldBeforeSave($args)
    {
        $new_model = $args['model'];
        if(!$new_model->id()){
            return;
        }
        $orig_model = FCom_CustomField_Model_Field::i()->orm('cf')->load($new_model->id());
        if (!$orig_model){
            return;
        }

        $f = FCom_IndexTank_Model_ProductFields::i()->orm()
                    ->where('field_name', FCom_IndexTank_Index_Product::i()->get_custom_field_name($orig_model))
                    ->where('type', 'category')
                    ->find_one();
        if ($f){
            $f->field_name = FCom_IndexTank_Index_Product::i()->get_custom_field_name($new_model);
            $f->save();
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
        $products = $cf_model->products();
        foreach($products as $product){
            FCom_IndexTank_Index_Product::i()->delete_custom_field($product, $cf_model);
        }

        $f = FCom_IndexTank_Model_ProductFields::i()->orm()
                    ->where('field_name', get_custom_field_name($cf_model))
                    ->where('type', 'category')
                    ->find_one();
        if ($f){
            $f->delete();
        }
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
                    array('view', 'root', 'do'=>array(
                        array('addNav', 'indextank', array('label'=>'IndexDen', 'pos'=>100)),
//                        array('addNav', 'indextank/dashboard', array('label'=>'Dashboard', 'pos'=>100, 'href'=>$baseHref.'/dashboard')),
                        array('addNav', 'indextank/product_fields', array('label'=>'Product fields', 'href'=>BApp::href('indextank/product_fields'))),
                    ))),
       /*         '/indextank/dashboard'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('indextank/dashboard')),
                    array('view', 'root', 'do'=>array(array('setNav', 'indextank/dashboard'))),
                ),*/
                '/indextank/product_fields'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('indextank/product_fields')),
                    array('view', 'root', 'do'=>array(array('setNav', 'indextank/product_fields'))),
                ),
                '/indextank/product_fields/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('indextank/product_fields-form')),
                    array('view', 'root', 'do'=>array(array('setNav', 'indextank/product_fields'))),
                    array('view', 'indextank/product_fields-form', 'set'=>array(
                        'tab_view_prefix' => 'indextank/product_fields-form/',
                    ), 'do'=>array(
                        array('addTab', 'main', array('label'=>'Product Field', 'pos'=>10))
                    )),
                ),
                '/settings'=>array(
                    array('view', 'settings', 'do'=>array(
                        array('addTab', 'FCom_IndexTank', array('label'=>'IndexDen API', 'async'=>true))
                    )))

            ));


    }

    static protected function _initButtonsOnProductsPage()
    {
        BGanon::i()->ready(function($args) {
            $insert = '<button class="st1 sz2 btn" onclick="ajax_index_all_products();"><span>Index All Products</span></button>
                <button class="st1 sz2 btn" onclick="ajax_products_clear_all();"><span>Clear Products Index</span></button>
<script type="text/javascript">
    function ajax_index_all_products() { $.ajax({ type: "GET", url: "'.BApp::href('indextank/products/index').'"})
        .done(function( msg ) { alert( msg ); }); }
    function ajax_products_clear_all() { $.ajax({ type: "DELETE", url: "'.BApp::href('indextank/products/index').'"})
        .done(function( msg ) { alert( msg ); }); }
</script>
';
            if (($el = BGanon::i()->find('header.adm-page-title div.btns-set', 0))) {
                $el->setInnerText($insert.$el->getInnerText());
            }
        }, array('on_path'=>'/catalog/products'));
    }
}