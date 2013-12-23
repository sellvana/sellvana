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

    static public function getTopNavCategories($maxLevel = 1)
    {
        if (BConfig::i()->get('modules/FCom_Frontend/nav_top/type') == 'categories_root') {
            $rootId = BConfig::i()->get('modules/FCom_Frontend/nav_top/root_category');
            if (!$rootId){
                $rootId = 1;
            }
            $categories = static::orm()->where('parent_id', $rootId)->find_many_assoc();
        } else {
            $categories = static::orm()->where('top_menu', 1)->find_many_assoc();
        }
        if ($maxLevel === 2) {
            if(sizeof($categories)===0) {
                $subcats=array();
            } else {
                $subcats = static::orm()->where_in('parent_id', array_keys($categories))->find_many();
            }
            $children = array();
            foreach ($subcats as $sc) {
                $children[$sc->get('parent_id')][] = $sc;
            }
            foreach ($children as $cId => $cs) {
                $categories[$cId]->set('children', $cs);
            }
        }
        return array_values($categories);
    }

    public function onAfterCreate()
    {
        parent::onAfterCreate();

        $this->set(array(
            'show_products' => 1,
            'show_sidebar' => 1,
            'is_enabled' => 1,
        ));
    }

    public function onAfterSave()
    {
        parent::onAfterSave();
        $add_ids = explode(',', $this->get('product_ids_add'));
        $remove_ids = explode(',', $this->get('product_ids_remove'));
        $hlp = FCom_Catalog_Model_CategoryProduct::i();

        if (sizeof($add_ids)>0 && $add_ids[0] != '') {
            foreach ($add_ids as $pId) {
                $hlp->create(array('category_id' => $this->id(), 'product_id' => $pId))->save();
            }
        }
        if (sizeof($remove_ids)>0 && $remove_ids[0] != '') {
            $hlp->delete_many(array('category_id' => $this->id(), 'product_id' => $remove_ids));
        }
    }

    public function imagePath()
    {
        return 'media/category/images/';
    }

    /**
     * check this model have image or not, if yes, return dir or url base on type
     * @param string $type (url|dir|fulldir)
     * @return bool|string
     */
    public function image($type = 'url')
    {
        $dir = FCom_Core_Main::i()->dir($this->imagePath());
        $filename = $dir.$this->id.'.jpg';
        if (is_file($filename)) {
            switch ($type) {
                case 'url':
                default:
                    $return = BApp::href('/media/category/images/'.$this->id.'.jpg');
                    break;
                case 'dir': //usually use with resize.php
                    $return = $this->imagePath().$this->id.'.jpg';
                    break;
                case 'fulldir':
                    $return = $filename;
                    break;
            }
            return $return;
        }
        return false;
    }

    public function deleteImage()
    {
        $image = $this->image('fulldir');
        if ($image) {
            clearstatcache(true, $image);
            return unlink($image);
        }
        return true;
    }
}
