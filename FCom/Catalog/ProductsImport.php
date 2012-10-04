<?php

class FCom_Catalog_ProductsImport extends BImport
{
    protected $fields = array(
            'product.manuf_sku' => array('pattern'=>'manuf.*sku'),
            'product.product_name' => array('pattern'=>'product.*name'),
            'product.short_description' => array('pattern'=>'short.*description'),
            'product.description' => array('pattern'=>'description'),
            'product.base_price' => array('pattern'=>'base.*price')
        );

    protected $dir = 'products';
    protected $model = 'FCom_Catalog_Model_Product';
}