<?php

class FCom_Catalog_ProductsImport extends BImport
{
    protected $fields = array(
        'product.manuf_sku' => array('pattern'=>'manuf.*sku'),
        'product.product_name' => array('pattern'=>'product.*name'),
        'product.short_description' => array('pattern'=>'short.*description'),
        'product.description' => array('pattern'=>'description'),
        'product.url_key' => array('pattern'=>'url.*key'),
        'product.base_price' => array('pattern'=>'base.*price'),
        'product.notes' => array('pattern'=>'notes'),
        'product.weight' => array('pattern'=>'weight'),
        'product.image_url' => array('pattern'=>'image*url'),
        'product.avg_rating' => array('pattern'=>'avg*rating'),
        'product.num_reviews' => array('pattern'=>'num*reviews'),
        'product.disabled' => array('pattern'=>'disabled'),
        'product.categories' => array('pattern'=>'categories'),
        'product.images' => array('pattern'=>'images'),
    );

    protected $dir = 'products';
    protected $model = 'FCom_Catalog_Model_Product';

    public function updateFieldsDueToInfo($info=null)
    {
        $cfFields = FCom_CustomField_Model_Field::i()->getListAssoc();
        $cfKeys = array_keys($cfFields);
        $dataKeys = $info['first_row'];
        $cfIntersection = array_intersect($cfKeys, $dataKeys);
        if ($cfIntersection) {
            foreach ($cfIntersection as $f) {
                if (!isset($this->fields['product.'.$f])) {
                    $this->fields['product.'.$f] = array('pattern' => $f);
                }
            }
        }
    }
}