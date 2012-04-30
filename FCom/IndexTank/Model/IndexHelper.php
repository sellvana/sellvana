<?php

class FCom_IndexTank_Model_IndexHelper extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_index_helper';

    public function products($index)
    {
        $helper = FCom_IndexTank_Model_IndexHelper::i()->orm()->where("index", $index)->find_one();
        $products = FCom_Catalog_Model_Product::i()->orm()->where_gt("update_dt", $helper->checkpoint)->find_many();
        return $products;
    }
}
