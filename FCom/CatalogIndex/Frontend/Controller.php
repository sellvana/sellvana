<?php

class FCom_CatalogIndex_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_test()
    {
        //FCom_CatalogIndex::i()->indexProducts(FCom_Catalog_Model_Product::i()->orm()->find_many());
        FCom_CatalogIndex::i()->findProducts('test', array('color'=>'Blue'), 'product_name');
    }
}