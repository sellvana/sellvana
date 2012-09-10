<?php

class FCom_Catalog_Model_Category extends FCom_Core_Model_TreeAbstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_category';
    protected static $_cacheAuto = true;

    public function productsORM()
    {
        return FCom_Catalog_Model_Product::i()->orm('p')
            ->join('FCom_Catalog_Model_CategoryProduct', array('pc.product_id','=','p.id'), 'pc')
            ->where('pc.category_id', $this->id);
    }

    public function products()
    {
        return $this->productsORM()->find_many();
    }

    public function url()
    {
        return BApp::href($this->url_path);
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

    /**
     * Add category to top menu
     * @param type $set
     */
    public function setInMenu($set=true)
    {
        $this->top_menu = $set;
        $this->save();
    }
}