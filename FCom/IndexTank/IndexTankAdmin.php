<?php

class FCom_IndexTank_Admin extends BClass
{
    static public function bootstrap()
    {
        $module = BApp::m();
        $module->base_src .= '/Admin';

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

        BFrontController::i()
            ->route('GET /indextank/dashboard', 'FCom_IndexTank_Admin_Controller.dashboard')

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

    static public function onProductAfterSave($args)
    {
        $product = $args['model'];
        FCom_IndexTank_Index_Product::i()->add($product);
    }

    static public function onProductBeforeDelete($args)
    {
        $product = $args['model'];
        FCom_IndexTank_Index_Product::i()->delete($product);
    }

    static public function onCategoryAfterSave($args)
    {
        $category = $args['model'];
        $products = $category->products();
        foreach($products as $product){
            FCom_IndexTank_Index_Product::i()->update_categories($product);
        }
    }

    static public function onCategoryBeforeDelete($args)
    {
        $category = $args['model'];
        $products = $category->products();
        foreach($products as $product){
            FCom_IndexTank_Index_Product::i()->delete_categories($product, $category);
        }
    }

    static public function onCustomFieldAfterSave($args)
    {
        $cf_model = $args['model'];
        $products = $cf_model->products();
        foreach($products as $product){
            FCom_IndexTank_Index_Product::i()->update_categories($product);
        }
    }
    static public function onCustomFieldBeforeDelete($args)
    {
        $cf_model = $args['model'];
        $products = $cf_model->products();
        foreach($products as $product){
            FCom_IndexTank_Index_Product::i()->delete_custom_field($product, $cf_model);
        }
    }


    static public function layout()
    {
        $baseHref = BApp::href('indextank');
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'root', 'do'=>array(
                        array('addNav', 'indextank', array('label'=>'IndexDen', 'pos'=>100)),
                        array('addNav', 'indextank/dashboard', array('label'=>'Dashboard', 'pos'=>100, 'href'=>$baseHref.'/dashboard')),
                    ))),
                '/indextank/dashboard'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('indextank/dashboard')),
                    array('view', 'root', 'do'=>array(array('setNav', 'indextank/dashboard'))),
                ),
                '/settings'=>array(
                    array('view', 'settings', 'do'=>array(
                        array('addTab', 'FCom_IndexTank', array('label'=>'IndexDen API', 'async'=>true))
                    )))

            ));


    }

}