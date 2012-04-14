<?php

class FCom_IndexTank_Admin extends BClass
{
    static public function bootstrap()
    {
        BGanon::i()->ready(function($args) {
            $insert = '<button class="st1 sz2 btn" onclick="ajax_index_all_products();"><span>Index All Products</span></button>
<script type="text/javascript">
    function ajax_index_all_products() { $.ajax({ type: "GET", url: "'.BApp::href('indextank/products/index').'"})
        .done(function( msg ) { alert( msg ); }); }
</script>';
            if (($el = BGanon::i()->find('header.adm-page-title div.btns-set', 0))) {
                $el->setInnerText($insert.$el->getInnerText());
            }
        }, array('on_path'=>'/catalog/products'));

        BFrontController::i()
            ->route('GET /indextank/products/index', 'FCom_IndexTank_Admin::productsIndexAll');


        BLayout::i()->addAllViews('Admin/views');
        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_IndexTank_Admin::layout')
                    ->on('FCom_Catalog_Model_Product::afterSave', 'FCom_IndexTank_Admin::onProductAfterSave')
                    ->on('FCom_Catalog_Model_Product::beforeDelete', 'FCom_IndexTank_Admin::onProductBeforeDelete')

                    //now for categories
                    ->on('FCom_Catalog_Model_Category::afterSave', 'FCom_IndexTank_Admin::onCategoryAfterSave')
                    ->on('FCom_Catalog_Model_Category::beforeDelete', 'FCom_IndexTank_Admin::onCategoryBeforeDelete')
            ;
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
/*
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
        $category = $args['model'];
        $products = $category->products();
        foreach($products as $product){
            FCom_IndexTank_Index_Product::i()->delete_categories($product, $category);
        }
    }
*/


    static public function layout()
    {
        BLayout::i()
            ->layout(array(
                '/settings'=>array(
                    array('view', 'settings', 'do'=>array(
                        array('addTab', 'FCom_IndexTank', array('label'=>'IndexDen API', 'async'=>true))
                        )))
            ));


    }

}