<?php

/**
 * Class Sellvana_Catalog_Model_Category
 *
 * @property int $is_top_menu
 * @property int $show_content
 * @property string $content
 * @property int $show_products
 * @property int $show_sub_cat
 * @property string $layout_update
 * @property string $page_title
 * @property string $description
 * @property string $meta_description
 * @property string $meta_keywords
 * @property int $show_sidebar
 * @property int $show_view
 * @property string $view_name
 * @property string $page_parts
 * @property string $image_url
 * @property int $is_featured
 * @property string $featured_image_url
 * @property string $nav_callout_image_url
 *
 * DI
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_CategoryProduct $Sellvana_Catalog_Model_CategoryProduct
 * @property FCom_Core_Model_ImportExport_Id $FCom_Core_Model_ImportExport_Id
 * @property FCom_Core_Model_ImportExport_Site $FCom_Core_Model_ImportExport_Site
 */
class Sellvana_Catalog_Model_Category extends FCom_Core_Model_TreeAbstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_category';
    protected static $_cacheAuto = true;

    protected static $_urlPrefix;

    protected static $_importExportProfile = [
        'skip'    => [
            'id_path'
        ],
        'related' => [
            'parent_id' => 'Sellvana_Catalog_Model_Category.id',
        ],
        'unique_key' => 'url_path'
    ];

    public function productsORM()
    {
        return $this->Sellvana_Catalog_Model_Product->orm('p')
            ->join('Sellvana_Catalog_Model_CategoryProduct', ['pc.product_id', '=', 'p.id'], 'pc')
            ->where('pc.category_id', $this->id);
    }

    /**
     * @return Sellvana_Catalog_Model_Product[]
     */
    public function products()
    {
        return $this->productsORM()->find_many();
    }

    /**
     * @return mixed
     */
    public function urlPrefix()
    {
        if (empty(static::$_urlPrefix)) {
            $prefix = $this->BConfig->get('modules/Sellvana_Catalog/url_prefix');
            switch ($this->BConfig->get('web/language_in_url')) {
                case 'lang':
                    $prefix .= $this->BLocale->getCurrentLanguage() . '/';
                    break;
                case 'locale':
                    $prefix .= $this->BLocale->getCurrentLocale() . '/';
                    break;
            }
            static::$_urlPrefix = $prefix;
        }
        return static::$_urlPrefix;
    }

    /**
     * @return string
     */
    public function url()
    {
        $prefix = $this->urlPrefix();
        return $this->BApp->frontendHref($prefix . $this->url_path);
    }

    /**
     * @param $args
     * @return bool
     * @throws BException
     */
    public function onReorderAZ($args)
    {
        $c = $this->load($args['id']);
        if (!$c) {
            throw new BException('Invalid category ID: ' . $args['id']);
        }

        $c->reorderChildrenAZ(!empty($args['recursive']));
        $this->cacheSaveDirty();
        return true;
    }

    /**
     * Add category to top menu
     * @param bool $set
     */
    public function setInMenu($set = true)
    {
        $this->is_top_menu = $set;
        $this->save();
    }

    /**
     * @param $categories
     * @return array
     */
    public function prepareApiData($categories)
    {
        $result = [];
        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name'  => $category->node_name,
                'url'   => $category->url_key,
                'path'  => $category->id_path,
                'children'  => $category->num_children
            ];
        }
        return $result;
    }

    /**
     * @return array
     */
    public function parentNodeList()
    {
        $categories = self::orm()->find_many();
        $result = [];
        if (empty($categories)) {
            return $result;
        }

        foreach ($categories as $cat) {
            $result[$cat->parent_id][$cat->node_name] = $cat;
        }
        return $result;
    }

    /**
     * @return int
     */
    public function inMenu()
    {
        return $this->is_top_menu;
    }

    public function getRootCategories()
    {
        $rootCats = $this->orm()->where('parent_id', null)->find_many_assoc('id', 'node_name');
        foreach ($rootCats as $id => $name) {
            if (!$name) {
                $rootCats[$id] = $this->BLocale->_('Default');
            }
        }
        return $rootCats;
    }

    /**
     * @param int $maxLevel 1 or 2
     * @return array
     */
    public function getTopNavCategories($maxLevel = 1)
    {
        /** @var BORM $orm */
        $orm = $this->orm()->order_by_asc('sort_order')->where('is_enabled', 1);
        $navType = $this->BConfig->get('modules/FCom_Frontend/nav_top/type');
        $whereOr = [];
        if ($navType === 'root_only' || $navType === 'root_selected' || $navType === 'categories_root') {
            $rootId = $this->BConfig->get('modules/FCom_Frontend/nav_top/root_category');
            if (!$rootId) {
                $rootId = 1;
            }
            $whereOr['parent_id'] = (int)$rootId;
        } elseif ($navType === 'selected' || $navType === 'root_selected') {
            $whereOr['is_top_menu'] = 1;
        }
        $orm->where_complex(['OR' => $whereOr]);
        $categories = $orm->find_many_assoc();
        if ($maxLevel === 2) {
            if (sizeof($categories) === 0) {
                $subcats = [];
            } else {
                $subcats = $this->orm()->where_in('parent_id', array_keys($categories))
                    ->where('is_enabled', 1)->order_by_asc('parent_id')->order_by_asc('sort_order')->find_many();
            }
            $children = [];
            foreach ($subcats as $sc) {
                $children[$sc->get('parent_id')][] = $sc;
            }
            foreach ($children as $cId => $cs) {
                $categories[$cId]->set('children', $cs);
            }
        }
        return array_values($categories);
    }

    /**
     * @return $this[]
     */
    public function getFeaturedCategories()
    {
        return $this->orm()->where('is_featured', 1)->find_many();
    }

    public function onAfterCreate()
    {
        parent::onAfterCreate();
        if (!$this->BDebug->is(BDebug::MODE_IMPORT)) {
            $this->set([
                'show_products' => 1,
                'show_sidebar' => 1,
                'is_enabled' => 1,
            ]);
        }
    }

    public function onAfterSave()
    {
        parent::onAfterSave();
        $addIds = explode(',', $this->get('product_ids_add'));
        $removeIds = explode(',', $this->get('product_ids_remove'));
        $hlp = $this->Sellvana_Catalog_Model_CategoryProduct;

        if (sizeof($addIds) > 0 && $addIds[0] != '') {
            $exists = $hlp->orm('cp')->where('category_id', $this->id())->where_in('product_id', $addIds)
                ->find_many_assoc('product_id');
            foreach ($addIds as $pId) {
                if (empty($exists[$pId])) {
                    $hlp->create(['category_id' => $this->id(), 'product_id' => $pId])->save();
                }
            }

        }
        if (sizeof($removeIds) > 0 && $removeIds[0] != '') {
            $hlp->delete_many(['category_id' => $this->id(), 'product_id' => $removeIds]);
        }
        $this->BEvents->fire(__METHOD__ . ':products', ['model' => $this, 'add_ids' => $addIds, 'remove_ids' => $removeIds]);
    }

    /**
     * @param bool $onlyEnabled
     * @return array
     */
    public function getPageParts($onlyEnabled = false)
    {
        $allParts = [
            'content' => 'Custom Content',
            'view' => 'Block / Page',
            'sub_cat' => 'Subcategories',
            'products' => 'Products',
        ];
        if ($onlyEnabled) {
            foreach ($allParts as $k => $l) {
                if (!$this->get('show_' . $k)) {
                    unset($allParts[$k]);
                }
            }
        }
        if (!$this->get('page_parts')) {
            return $allParts;
        }
        $parts = explode(',', $this->get('page_parts'));
        $result = [];
        foreach ($parts as $k) {
            $result[$k] = isset($allParts[$k]) ? $allParts[$k] : null;
        }
        return $result;
    }

    /**
     * @param $cloneNode
     * @return $this
     */
    public function onAfterClone(&$cloneNode)
    {
        //after clone categories, add products associate
        $products = $this->products();
        $tCategoryProduct = $this->Sellvana_Catalog_Model_CategoryProduct->table();
        if ($products) {
            $sql = "INSERT INTO {$tCategoryProduct} (product_id, category_id) VALUES";
            foreach ($products as $product) {
                /** @var Sellvana_Catalog_Model_Product */
                $sql .= ' (' . $product->get('id') . ', ' . $cloneNode->id . '),';
            }
            $sql = substr($sql, 0, strlen($sql) - 1);
            $this->Sellvana_Catalog_Model_CategoryProduct->orm()->raw_query($sql)->execute();
        }
        return $this;
    }

    /**
     * @param $args
     * @throws BException
     */
    public function onImportAfterBatch($args)
    {
        $importId = $args['import_id'];
        $importSite = $this->FCom_Core_Model_ImportExport_Site->load($importId, 'site_code');
        if (!$importSite) {
            return;
        }
        $toUpdate = $this->orm();
        if (isset($args['records']) && count($args['records'])) {
            $ids = array_keys($args['records']);
            $toUpdate->where_in('id', $ids);
        } else {
            $toUpdate->where(['OR' => ['parent_id IS NULL', 'id_path IS NULL']]);

        }

        $toUpdate = $toUpdate->find_many_assoc();

        if (empty($toUpdate)) {
            return;
        }
//        if ( isset( $toUpdate[ 1 ] ) && $toUpdate[ 1 ]->get( "level" ) == null) { // remove root category
//            unset( $toUpdate[ 1 ] );
//        }
        $ids = array_keys($toUpdate);
        $importData = $this->FCom_Core_Model_ImportExport_Id->orm()
            ->join(
              'FCom_Core_Model_ImportExport_Model',
              'iem.id=model_id and iem.model_name=\'' . $this->origClass() . '\'',
              'iem'
            )
            ->where(['site_id' => $importSite->id(), 'local_id' => $ids], null)
            ->find_many();

        if (empty($importData)) {
            $this->BDebug->log($this->BLocale->_("Could not update category data, missing import details"));
            return;
        }

        $relations = [];

        foreach ($importData as $item) {
            $rel = $item->get('relations');
            if (empty($rel)) {
                continue;
            }
            $relations[$item->get('local_id')] = json_decode($rel, true);
        }
        unset($rel);

        $fetch = [];
        foreach ($relations as $v) {
            foreach ($v as $id) {
                if (!isset($fetch[$id])) {
                    $fetch[$id] = 1;
                }
            }
        }

        if (count(array_keys($fetch))){
            $relatedData = $this->FCom_Core_Model_ImportExport_Id->orm()
                ->join(
                  'FCom_Core_Model_ImportExport_Model',
                  'iem.id=model_id and iem.model_name=\'' . $this->origClass() . '\'',
                  'iem'
                )
                ->where(['site_id' => $importSite->id(), 'import_id' => array_keys($fetch)], null)
                ->find_many_assoc('import_id');

            foreach ($relations as $k => $v) {
                $model = $toUpdate[$k];
                foreach ($v as $field => $r) {
                    if (!empty($relatedData[$r])) {
                        $rel = $relatedData[$r];
                        $model->set($field, $rel->get('local_id'));
                    }
                }
            }
        }

        foreach ($toUpdate as $model) {
            /** @var Sellvana_Catalog_Model_Category $model */
            $model->generateIdPath()->recalculateNumDescendants()->save();
        }
    }

    public function getFlatCategories($separator = ' > ', $rootCategory = null)
    {
        if (null === $rootCategory) {
            $rootCategory = $this->BConfig->get('modules/FCom_Frontend/nav_top/root_category', 1);
        }
        $categories = $this->orm()->select('id')->select('full_name')->order_by_asc('full_name')
            ->find_many_assoc('id', 'full_name');
        foreach ($categories as $id => &$name) {
            $name = str_replace('|', $separator, $name);
        }
        unset($name);
        return $categories;
    }
}
