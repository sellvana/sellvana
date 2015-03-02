<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_CustomField_Model_ProductVariantImage extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_variant_image';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'related' => [
            'product_id' => 'Sellvana_Catalog_Model_Product.id',
            'variant_id' => 'Sellvana_CustomField_Model_ProductVariant.id',
            'file_id' => 'FCom_Core_Model_MediaLibrary.id',
        ],
        'unique_key' => ['product_id', 'variant_id', 'file_id'],
    ];
}