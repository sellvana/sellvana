<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Catalog_Model_CategoryProduct extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_category_product';

    protected static $_importExportProfile = [
        'skip'    => ['id'],
        'related' => [
            'product_id'  => 'FCom_Catalog_Model_Product.id',
            'category_id' => 'FCom_Catalog_Model_Category.id',
        ],
        'unique_key' => [
            'product_id',
            'category_id',
        ],
    ];

    public function category()
    {
        if (!$this->category) {
            $this->category = FCom_Catalog_Model_Category::i()->load($this->category_id);
        }
        return $this->category;
    }
}