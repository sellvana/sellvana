<?php

/**
 * Class Sellvana_IndexTank_Model_IndexHelper
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_IndexTank_Model_IndexHelper $Sellvana_IndexTank_Model_IndexHelper
 */

class Sellvana_IndexTank_Model_IndexHelper extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_index_helper';

    public function productsByIndex($index)
    {
        $helper = $this->Sellvana_IndexTank_Model_IndexHelper->orm()->where("index", $index)->find_one();
        $products = $this->Sellvana_Catalog_Model_Product->orm()->where_gt("update_at", $helper->checkpoint)->find_many();
        return $products;
    }
}
