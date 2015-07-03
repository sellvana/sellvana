<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_ProductsImport
 */
class Sellvana_Catalog_ProductsImport extends BImport
{
    protected $fields = [
        'product.product_sku' => ['pattern' => 'sku'],
        'product.product_name' => ['pattern' => 'product.*name|name'],
        'product.short_description' => ['pattern' => 'short.*description'],
        'product.description' => ['pattern' => 'description'],
        'product.url_key' => ['pattern' => 'url.*key'],
        'product.price.base' => ['pattern' => 'base.*price|price'],
        'product.price.sale' => ['pattern' => 'sale.*price|sale'],
        'product.price.sale.from_date' => ['pattern' => 'sale.*from.*date|from'],
        'product.price.sale.to_date' => ['pattern' => 'sale.*to.*date|to'],
        'product.price.cost' => ['pattern' => 'cost.*price|cost'],
        'product.price.msrp' => ['pattern' => 'msrp.*price|msrp'],
        'product.price.map'  => ['pattern' => 'map.*price|map'],
        'product.price.tier' => ['pattern' => 'tier.*price|tier'],
        'product.notes' => ['pattern' => 'notes'],
        'product.ship_weight' => ['pattern' => 'ship.*weight'],
        'product.net_weight' => ['pattern' => 'net.*weight'],
        'product.image_url' => ['pattern' => 'image*url|thumbnail'],
        'product.avg_rating' => ['pattern' => 'avg*rating'],
        'product.num_reviews' => ['pattern' => 'num*reviews'],
        'product.is_hidden' => ['pattern' => 'hidden|disable'],
        'product.categories' => ['pattern' => 'categories|category'],
        'product.images' => ['pattern' => 'images|image'],
        'product.uom' => ['pattern' => 'uom'],
        'product.create_at' => ['created'],
        'product.update_at' => ['updated']
    ];

    protected $dir = 'products';
    protected $model = 'Sellvana_Catalog_Model_Product';

    protected $allowedFileTypes = ['txt', 'csv'];

    public function updateFieldsDueToInfo($info = null)
    {
        $this->BEvents->fire(__METHOD__, ['info' => $info, 'object' => $this]);
    }
}
