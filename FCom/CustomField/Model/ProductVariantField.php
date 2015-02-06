<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CustomField_Model_ProductVariantField extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_variant_field';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'related' => [
            'product_id' => 'FCom_Catalog_Model_Product.id',
            'variant_id' => 'FCom_CustomField_Model_ProductVariant.id',
            'field_id' => 'FCom_CustomField_Model_Field.id',
            'varfield_id' => 'FCom_CustomField_Model_Varfield.id',
            'option_id' => 'FCom_CustomField_Model_FieldOption.id',
        ],
        'unique_key' => ['product_id', 'variant_id', 'field_id'],
    ];
}