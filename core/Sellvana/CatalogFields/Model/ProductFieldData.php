<?php

/**
 * Class Sellvana_CatalogFields_Model_ProductFieldData
 *
 */
class Sellvana_CatalogFields_Model_ProductFieldData extends FCom_Core_Model_Abstract_FieldData
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product_field_data';
    protected static $_fieldType = 'product';
    protected static $_mainModel = 'Sellvana_Catalog_Model_Product';
    protected static $_mainModelKeyField = 'product_id';
    protected static $_useMultisite = true;
}
