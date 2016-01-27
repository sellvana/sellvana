<?php

class Sellvana_Catalog_Model_ProductHistory extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_product_history';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['product_id', 'create_at'],
        'related'    => ['product_id' => 'Sellvana_Catalog_Model_Product.id'],
    ];
}
