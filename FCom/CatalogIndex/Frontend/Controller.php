<?php

class FCom_CatalogIndex_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_test()
    {
        #FCom_CatalogIndex::i()->indexProducts(true);//FCom_Catalog_Model_Product::i()->orm()->find_many());
        $result = FCom_CatalogIndex::i()->findProducts('lorem', array('color'=>'Green', 'size'=>'Medium'), 'product_name');
        var_dump($result['orm']->find_many());
    }
}