<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CustomField_Model_ProductVarfield extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_varfield';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'related' => [
            'product_id' => 'FCom_Catalog_Model_Product.id',
            'field_id' => 'FCom_CustomField_Model_Field.id',
        ],
        'unique_key' => ['product_id', 'field_id'],
    ];
}