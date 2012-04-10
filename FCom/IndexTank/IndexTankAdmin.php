<?php

class FCom_IndexTank_Admin extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route('GET /indextank/products/index', 'FCom_IndexTank_Admin::products_index_all');

        BLayout::i()->addAllViews('Admin/views');
        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_IndexTank_Admin::layout')
                    ->on('FCom_Catalog_Model_Product::afterSave', 'FCom_IndexTank_Admin::onProductAfterSave')
                    ->on('FCom_Catalog_Model_Product::beforeDelete', 'FCom_IndexTank_Admin::onProductBeforeDelete');
    }

    static public function products_index_all()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')->select('p.*');
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