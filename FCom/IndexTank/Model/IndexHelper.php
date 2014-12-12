<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_IndexTank_Model_IndexHelper
 *
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_IndexTank_Model_IndexHelper $FCom_IndexTank_Model_IndexHelper
 */

class FCom_IndexTank_Model_IndexHelper extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_index_helper';

    public function productsByIndex($index)
    {
        $helper = $this->FCom_IndexTank_Model_IndexHelper->orm()->where("index", $index)->find_one();
        $products = $this->FCom_Catalog_Model_Product->orm()->where_gt("update_at", $helper->checkpoint)->find_many();
        return $products;
    }
}
