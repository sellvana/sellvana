<?php

class FCom_IndexTank_Admin extends BClass
{
    static public function bootstrap()
    {
        BphpQuery::i()->ready(function($args) {
            $html = '<button class="st1 sz2 btn" onclick="ajax_index_all_products();"><span>Index All Products</span></button>
<script type="text/javascript">
    function ajax_index_all_products() { $.ajax({ type: "GET", url: "'.BApp::href('indextank/products/index').'"})
        .done(function( msg ) { alert( msg ); }); }
</script>';
            $args['doc']['header.adm-page-title div.btns-set']->append($html);
        }, array('on_path'=>'/catalog/products'));

        BFrontController::i()
            ->route('GET /indextank/products/index', 'FCom_IndexTank_Admin::productsIndexAll');

        BLayout::i()->addAllViews('Admin/views')
            ->afterTheme('FCom_IndexTank_Admin::layout');

        BPubSub::i()->on('FCom_Catalog_Model_Product::afterSave', 'FCom_IndexTank_Admin::onProductAfterSave')
                    ->on('FCom_Catalog_Model_Product::beforeDelete', 'FCom_IndexTank_Admin::onProductBeforeDelete');
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