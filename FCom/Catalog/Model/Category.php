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
        $this->is_top_menu = $set;
        $this->save();
    }

    public function prepareApiData($categories)
    {
        $result = array();
        foreach($categories as $category) {
            $result[] = array(
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name'  => $category->node_name,
                'url'   => $category->url_key,
                'path'  => $category->id_path,
                'children'  => $category->num_children
            );
        }
        return $result;
    }

    public function parentNodeList()
    {
        $categories = self::orm()->find_many();
        $result = array();
        if (empty($categories)) {
            return $result;
        }

        foreach($categories as $cat) {
            $result[$cat->parent_id][$cat->node_name] = $cat;
        }
        return $result;
    }

    public function inMenu()
    {
        return $this->is_top_menu;
    }

    static public function getTopNavCategories()
    {
        if (BConfig::i()->get('modules/FCom_Frontend/nav_top/type') == 'categories_root') {
            $rootId = BConfig::i()->get('modules/FCom_Frontend/nav_top/root_category');
            if (!$rootId){
                $rootId = 1;
            }
            $categories = FCom_Catalog_Model_Category::i()->orm()->where('parent_id', $rootId)->find_many();
        } else {
            $categories = FCom_Catalog_Model_Category::i()->orm()->where('top_menu', 1)->find_many();
        }
        return $categories;
    }
}