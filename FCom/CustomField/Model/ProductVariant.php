<?php

/**
 * @property string  field_values    (field1=value1&field2=value2&field3=value3)
 * @property string  variant_sku     (PROD_VAL1_VAL2_VAL3)
 * @property decimal variant_price
 * @property text    data_serialized
 *   -
 */
class FCom_CustomField_Model_ProductVariant extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_variant';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = ['related' => ['product_id' => 'FCom_Catalog_Model_Product.id'],
                                                    'unique_key' => ['product_id', 'field_values',],  ];


}
