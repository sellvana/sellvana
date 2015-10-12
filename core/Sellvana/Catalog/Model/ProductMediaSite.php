<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Model_ProductMediaSite
 *
 * @property FCom_Core_Main $FCom_Core_Main
 */

class Sellvana_Catalog_Model_ProductMediaSite extends FCom_Core_Model_Abstract
{
    protected static $_table               = 'fcom_product_media_site';
    protected static $_origClass           = __CLASS__;

    protected static $_importExportProfile = [
        'skip' => ['id'],
        'related' => [
            'media_id' => 'Sellvana_Catalog_Model_ProductMedia.id',
            'site_id'    => 'Sellvana_MultiSite_Model_Site.id',
        ],
        'unique_key' => ['media_id', 'site_id'],
        'unique_key_not_null' => ['media_id', 'site_id'],
    ];
}