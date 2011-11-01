<?php

class FCom_Catalog_Model_Category extends FCom_Core_Model_TreeAbstract
{
    protected static $_table = 'fcom_category';

    public function productsORM()
    {
        return FCom_Catalog_Model_Product::i()->factory()->table_alias('p')
            ->join('a_product_category', array('pc.product_id','=','p.id'), 'pc')
            ->where('pc.category_id', $this->id);
    }

    public function products()
    {
        return $this->productsORM()->find_many();
    }

    public function url()
    {
        return BApp::baseUrl().'/c/'.$this->url_path;
    }

    public function onReorderAZ($args)
    {
        $c = static::i()->load($args['id']);
        if (!$c) {
            throw new BException('Invalid category ID: '.$args['id']);
        }

        $c->reorderChildrenAZ(!empty($args['recursive']));
        static::i()->cacheSaveDirty();
        return true;
    }
}