<?php

class Sellvana_SalesTax_Model_ProductTax extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_salestax_product_tax';
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['product_id', 'product_class_id'],
        'related'    => [
            'product_id'       => 'Sellvana_Catalog_Model_Product.id',
            'product_class_id' => 'Sellvana_SalesTax_Model_ProductClass.id'
        ],
    ];

    public function getProductTaxClassIds($product)
    {
        return $this->orm()->where('product_id', $product->id())->find_many_assoc('id', 'product_class_id');
    }
}
