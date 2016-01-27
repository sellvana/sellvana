<?php

class Sellvana_Catalog_Model_InventorySkuHistory extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_inventory_sku_history';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['sku_id', 'create_at', 'unit_cost'],
        'related'    => ['sku_id'=>'Sellvana_Catalog_Model_InventorySku.id'],
    ];
}
