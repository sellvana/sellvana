<?php

class FCom_Catalog_Model_Category extends FCom_Core_Model_TreeAbstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_category';
    protected static $_cacheAuto = true;

    protected static $_urlPrefix;

    protected static $_importExportProfile = array(
        'skip'    => array(),
        'related' => array(
            'parent_id' => 'FCom_Catalog_Model_Category.id',
        ),
        'calc'    => array(
            'id_path' => 'FCom_Catalog_Model_Category.id',
        ),
        'unique_key' => 'url_path'
    );

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

    static public function urlPrefix()
    {
        if (empty(static::$_urlPrefix)) {
            static::$_urlPrefix = BConfig::i()->get('modules/FCom_Catalog/url_prefix');
        }
        return static::$_urlPrefix;
    }

    public function url()
    {
        $prefix = static::urlPrefix();
        return BApp::href($prefix . $this->url_path);
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
     * @param bool $set
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
        $addIds = explode(',', $this->get('product_ids_add'));
        $removeIds = explode(',', $this->get('product_ids_remove'));
        $hlp = FCom_Catalog_Model_CategoryProduct::i();

        if (sizeof($addIds)>0 && $addIds[0] != '') {
            $exists = $hlp->orm('cp')->where('category_id', $this->id())->where_in('product_id', $addIds)->find_many_assoc('product_id');
            foreach ($addIds as $pId) {
                if (empty($exists[$pId])) {
                    $hlp->create(array('category_id' => $this->id(), 'product_id' => $pId))->save();
                }
            }
        }
        if (sizeof($removeIds)>0 && $removeIds[0] != '') {
            $hlp->delete_many(array('category_id' => $this->id(), 'product_id' => $removeIds));
        }
        BEvents::i()->fire(__METHOD__.':products', array('model' => $this, 'add_ids' => $addIds, 'remove_ids' => $removeIds));
    }

    public function imagePath()
    {
        return 'media/category/images/';
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

    public function getPageParts($onlyEnabled = false)
    {
        $allParts = array(
            'content' => 'Custom Content',
            'view' => 'Block / Page',
            'sub_cat' => 'Subcategories',
            'products' => 'Products',
        );
        if ($onlyEnabled) {
            foreach ($allParts as $k => $l) {
                if (!$this->get('show_'.$k)) {
                    unset($allParts[$k]);
                }
            }
        }
        if (!$this->get('page_parts')) {
            return $allParts;
        }
        $parts = explode(',', $this->get('page_parts'));
        $result = array();
        foreach ($parts as $k) {
            $result[$k] = isset($allParts[$k]) ? $allParts[$k] : null;
        }
        return $result;
    }

    public function onAfterClone(&$cloneNode)
    {
        //after clone categories, add products associate
        $products = $this->products();
        if ($products) {
            $sql = "INSERT INTO fcom_category_product (product_id, category_id) VALUES";
            foreach ($products as $product) {
                /** @var FCom_Catalog_Model_Product */
                $sql .= ' ('.$product->get('id').', '.$cloneNode->id.'),';
            }
            $sql = substr($sql, 0, strlen($sql) - 1);
            FCom_Catalog_Model_CategoryProduct::i()->orm()->raw_query($sql)->execute();
        }
        return $this;
    }

    public function processAfterImport( $args )
    {
        $importId = $args[ 'import_id' ];
        $toUpdate = $this->orm()->where( 'parent_id IS NULL' )->find_many_assoc();
        if ( empty( $toUpdate ) ) {
            return;
        }
        $ids = array_keys( $toUpdate );
        $importData = FCom_Core_Model_ImportExport_Id::i()->orm()
            ->join(
              FCom_Core_Model_ImportExport_Model::i()->table(),
              'iem.id=model_id and iem.model_name=\'' . static::origClass() . '\'',
              'iem'
            )
            ->where( array( 'site_id' => $importId ) )
            ->where( array( 'local_id' => $ids ) )
            ->find_many();

        if(empty($importData)){
            BDebug::log( BLocale::_( "Could not update category data, missing import details" ));
            return;
        }

        foreach ( $importData as $item ) {
            $relations = $item->get( 'relations' );
            if ( empty( $relations ) ) {
                continue;
            }
            $relations = json_decode($relations);
        }

    }
}
