<?php

class FCom_Catalog_ProductsImport extends BImport
{
    protected $fields = array(
        'product.local_sku' => array('pattern'=>'sku'),
        'product.product_name' => array('pattern'=>'product.*name|name'),
        'product.short_description' => array('pattern'=>'short.*description'),
        'product.description' => array('pattern'=>'description'),
        'product.url_key' => array('pattern'=>'url.*key'),
        'product.base_price' => array('pattern'=>'base.*price|price'),
        'product.sale_price' => array('pattern'=>'sale.*price|price'),
        'product.notes' => array('pattern'=>'notes'),
        'product.ship_weight' => array('pattern'=>'ship.*weight'),
        'product.net_weight' => array('pattern'=>'net.*weight'),
        'product.image_url' => array('pattern'=>'image*url|thumbnail'),
        'product.avg_rating' => array('pattern'=>'avg*rating'),
        'product.num_reviews' => array('pattern'=>'num*reviews'),
        'product.is_hidden' => array('pattern'=>'hidden|disable'),
        'product.categories' => array('pattern'=>'categories|category'),
        'product.images' => array('pattern'=>'images|image'),
        'product.uom' => array('pattern'=>'uom'),
        'product.create_at' => array('created'),
        'product.update_at' => array('updated')
    );

    protected $dir = 'products';
    protected $model = 'FCom_Catalog_Model_Product';

    public function updateFieldsDueToInfo($info=null)
    {
        $cfFields = FCom_CustomField_Model_Field::i()->getListAssoc();
        $cfKeys = array_keys($cfFields);
//        $dataKeys = $info['first_row'];
        //$cfIntersection = array_intersect($cfKeys, $dataKeys);
        foreach($cfKeys as $key) {
            if (!isset($this->fields['product.'.$key])) {
                $this->fields['product.'.$key] = array('pattern' => $key);
            }
        }
        /*
        if ($dataKeys) {
            foreach ($dataKeys as $f) {
                if (!isset($this->fields['product.'.$f])) {
                    $this->fields['product.'.$f] = array('pattern' => $f);
                }
            }
        }
         *
         */
    }
}
