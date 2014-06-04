<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_IndexTank_Model_IndexHelper extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_index_helper';

    public function productsByIndex($index)
    {
        $helper = $this->FCom_IndexTank_Model_IndexHelper->orm()->where("index", $index)->find_one();
        $products = $this->FCom_Catalog_Model_Product->orm()->where_gt("update_at", $helper->checkpoint)->find_many();
        return $products;
    }

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Model_IndexHelper
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }
}
