<?php

class Sellvana_CatalogFields_Model_ProductVariantField extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_variant_field';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'related' => [
            'product_id' => 'Sellvana_Catalog_Model_Product.id',
            'variant_id' => 'Sellvana_CatalogFields_Model_ProductVariant.id',
            'field_id' => 'Sellvana_CatalogFields_Model_Field.id',
            'varfield_id' => 'Sellvana_CatalogFields_Model_ProductVarfield.id',
            'option_id' => 'Sellvana_CatalogFields_Model_FieldOption.id',
        ],
        'unique_key' => ['product_id', 'variant_id', 'field_id'],
    ];
}