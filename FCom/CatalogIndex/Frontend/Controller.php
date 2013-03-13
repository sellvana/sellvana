<?php

class FCom_CatalogIndex_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_test()
    {
        FCom_CatalogIndex::i()->indexProducts(true);//FCom_Catalog_Model_Product::i()->orm()->find_many());
        FCom_CatalogIndex::i()->indexGC();
        $result = FCom_CatalogIndex::i()->findProducts('lorem', array('color'=>'Green', 'size'=>'Medium'), 'product_name');
        echo "<pre>";
        print_r($result['facets']);
        print_r($result['orm']->find_many());
        echo "</pre>";
    }
}