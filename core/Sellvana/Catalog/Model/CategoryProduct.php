<?php

/**
 * Class Sellvana_Catalog_Model_CategoryProduct
 * @property int $id
 * @property int $product_id
 * @property int $category_id
 * @property int $sort_order
 *
 * relations
 * @property Sellvana_Catalog_Model_Category $category
 *
 * DI
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 */
class Sellvana_Catalog_Model_CategoryProduct extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_category_product';

    protected static $_importExportProfile = [
        'skip'    => ['id'],
        'related' => [
            'product_id'  => 'Sellvana_Catalog_Model_Product.id',
            'category_id' => 'Sellvana_Catalog_Model_Category.id',
        ],
        'unique_key' => [
            'product_id',
            'category_id',
        ],
    ];

    public function category()
    {
        if (!$this->category) {
            $this->category = $this->Sellvana_Catalog_Model_Category->load($this->category_id);
        }
        return $this->category;
    }
}
