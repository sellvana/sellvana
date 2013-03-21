<?php

class FCom_Catalog_Model_CategoryProduct extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_category_product';

    public function category()
    {
        if (!$this->category) {
            $this->category = FCom_Catalog_Model_Category::i()->load($this->category_id);
        }
        return $this->category;
    }
}