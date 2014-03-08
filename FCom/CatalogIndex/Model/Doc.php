<?php

class FCom_CatalogIndex_Model_Doc extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_index_doc';

    static public function flagReindex($productIds)
    {
        static::update_many(array('flag_reindex' => 1), array('id' => $productIds));
    }
}