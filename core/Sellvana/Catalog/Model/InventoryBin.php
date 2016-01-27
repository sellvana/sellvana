<?php

class Sellvana_Catalog_Model_InventoryBin extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_inventory_bin';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['title'],
    ];
}
