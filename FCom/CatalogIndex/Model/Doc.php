<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CatalogIndex_Model_Doc extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_index_doc';

    static public function flagReindex($productIds)
    {
        if (!$productIds) {
            return;
        }
        static::update_many(['flag_reindex' => 1], ['id' => $productIds]);
    }
}