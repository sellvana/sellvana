<?php

/**
 * Class Sellvana_CatalogIndex_Main
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property Sellvana_CatalogIndex_Main $Sellvana_CatalogIndex_Main
 * @property Sellvana_CatalogIndex_Model_Doc $Sellvana_CatalogIndex_Model_Doc
 * @property Sellvana_CatalogIndex_Model_Field $Sellvana_CatalogIndex_Model_Field
 * @property FCom_Core_Model_ImportExport_Id $FCom_Core_Model_ImportExport_Id
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */

class Sellvana_CatalogIndex_Main extends BClass
{
    protected static $_autoReindex = true;

    protected static $_prevAutoReindex;

    protected static $_filterParams;

    protected static $_indexers = [];

    protected $_activeIndexer;

    public function autoReindex($flag)
    {
        static::$_autoReindex = $flag;
    }

    public function parseUrl()
    {
        if (($getFilters = $this->BRequest->get('filters'))) {
            $getFiltersArr = explode('.', $getFilters);
            static::$_filterParams = [];
            foreach ($getFiltersArr as $filterStr) {
                if ($filterStr === '') {
                    continue;
                }
                $filterArr = explode('-', $filterStr, 2);
                if (!isset($filterArr[1])) {
                    continue;
                }
                $valueArr = explode(' ', $filterArr[1]);
                foreach ($valueArr as $v) {
                    if ($v === '') {
                        continue;
                    }
                    static::$_filterParams[$filterArr[0]][$v] = $v;
                }
            }
        }
        return static::$_filterParams;
    }

    public function getUrl($add = [], $remove = [])
    {
        $filters = [];
        $params = static::$_filterParams;
        if ($add) {
            foreach ($add as $fKey => $fValues) {
                foreach ((array)$fValues as $v) {
                    $params[$fKey][$v] = $v;
                }
            }
        }
        if ($remove) {
            foreach ($remove as $fKey => $fValues) {
                foreach ((array)$fValues as $v) {
                    unset($params[$fKey][$v]);
                }
            }
        }
        foreach ($params as $fKey => $fValues) {
            if ($fValues) {
                $filters[] = $fKey . '-' . join(' ', (array)$fValues);
            }
        }
        return $this->BUtil->setUrlQuery($this->BRequest->currentUrl(), ['filters' => join('.', $filters)]);
    }


    public function onProductAfterSave($args)
    {
        if (static::$_autoReindex) {
            $this->getIndexer()->indexProducts([$args['model']]);
        } else {
            $doc = $this->Sellvana_CatalogIndex_Model_Doc->load($args['model']->id());
            if ($doc) {
                $doc->set('flag_reindex', 1)->save();
            }
        }
    }

    public function onProductBeforeImport($args)
    {
        static::$_prevAutoReindex = static::$_autoReindex;
        static::$_autoReindex = false;
    }

    public function onProductAfterImport($args)
    {
        static::$_autoReindex = static::$_prevAutoReindex;
        $this->Sellvana_CatalogIndex_Model_Doc->flagReindex($args['product_ids']);
        if (static::$_autoReindex) {
            $this->getIndexer()->indexPendingProducts();
        }
    }

    /**
     * Run indexing process for marked products
     *
     * @param $args
     * @throws BException
     */
    public function onAfterCoreImport($args)
    {
        $this->getIndexer()->indexPendingProducts();
    }

    /**
     * Mark imported products as required to reindex
     *
     * @param $args
     */
    public function onProductAfterCoreImport($args){
        if (array_key_exists('import_id', $args)){
            $orm = $this->FCom_Core_Model_ImportExport_Id->orm('p');
            $orm->inner_join('FCom_Core_Model_ImportExport_Site', ['s.id', '=', 'p.site_id'], 's')
                ->inner_join('FCom_Core_Model_ImportExport_Model', ['m.id', '=', 'p.model_id'], 'm')
                ->left_outer_join('Sellvana_CatalogIndex_Model_Doc', ['i.id', '=', 'p.local_id'], 'i')
                ->select('p.local_id')
                ->group_by('p.local_id')
                ->where('s.site_code', $args['import_id'])
                ->where('m.model_name', 'Sellvana_Catalog_Model_Product')
                ->where_null('i.id');
            $ids = $orm->find_many_assoc('local_id','local_id');

            $now = $this->BDb->now();
            foreach ($ids as $id){
                $this->Sellvana_CatalogIndex_Model_Doc->create([
                    'id'=>$id,
                    'last_indexed' => $now,
                    'flag_reindex' => 1
                ])->save();
            }
        }
    }

    public function onCategoryAfterSave($args)
    {
        /** @var Sellvana_Catalog_Model_Category $cat */
        $cat = $args['model'];
        $addIds = explode(',', $cat->get('product_ids_add'));
        $removeIds = explode(',', $cat->get('product_ids_remove'));
        $reindexIds = [];
        if (sizeof($addIds) > 0 && $addIds[0] != '') {
            $reindexIds += $addIds;
        }
        if (sizeof($removeIds) > 0 && $removeIds[0] != '') {
            $reindexIds += $removeIds;
        }
        $this->getIndexer()->indexProducts($reindexIds);
    }

    /**
     * @param array $args
     */
    public function onBeforeRefreshDescendants($args)
    {
        /** @var FCom_Core_Model_TreeAbstract $model */
        $model = $args['model'];
        $resetUrl = $args['resetUrl'];
        $oldValues = (array)$model->old_values();
        if ($resetUrl && array_key_exists('url_key', $oldValues) && $model->get('url_key') != $oldValues['url_key']) {
            $subCategoriesIds = [$model->id()];
            foreach ($model->descendants() as $descendant) {
                $subCategoriesIds[] = $descendant->id();
            }

            $reindexIds = $this->Sellvana_Catalog_Model_Product->orm('p')
                ->join('Sellvana_Catalog_Model_CategoryProduct', ['pc.product_id', '=', 'p.id'], 'pc')
                ->where_in('pc.category_id', $subCategoriesIds)
                ->group_by('p.id')
                ->find_many_assoc('product_id', 'product_id');

            if (!empty($reindexIds)) {
                $this->Sellvana_CatalogIndex_Model_Doc->update_many(['flag_reindex' => 1], ['id' => $reindexIds]);
            }
        }
    }

    /**
     * @param array $args
     * @throws BException
     */
    public function onAfterRefreshDescendants($args)
    {
        $model = $args['model'];
        $resetUrl = $args['resetUrl'];
        $oldValues = $model->old_values();
        if ($resetUrl && array_key_exists('url_key', $oldValues) && $model->get('url_key') != $oldValues['url_key']) {
            $this->getIndexer()->indexPendingProducts();
        }
    }

    public function onCustomFieldAfterSave($args)
    {
        if ($this->BDebug->is(BDebug::MODE_INSTALLATION)) {
            return true;
        }

        if (static::$_autoReindex && !$args['model']->isNewRecord()) {
            $indexField = $this->Sellvana_CatalogIndex_Model_Field->load($args['model']->field_code, 'field_name');
            if ($indexField) {
                //TODO when a edited field is saved, it throws error
                //$this->Sellvana_CatalogIndex_Main->getIndexer()->reindexField($indexField);
            }
        }
    }

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_CatalogIndex' => 'Product Indexing Settings',
            'catalog_index' => 'Product Indexing',
        ]);

        $this->addIndexer('builtin', [
            'class' => 'Sellvana_CatalogIndex_Indexer',
            'label' => 'Built-in',
        ]);
    }

    /**
     * @param string $name
     * @param string|array $params
     * @return $this
     */
    public function addIndexer($name, $params = null)
    {
        if (null === $params) {
            $params = ['class' => $name];
        } elseif (is_string($params)) {
            $params = ['class' => $params];
        }
        if (empty($params['label'])) {
            $params['label'] = $name;
        }
        static::$_indexers[$name] = $params;
        return $this;
    }

    /**
     * @return Sellvana_CatalogIndex_Indexer
     * @throws BException
     */
    public function getIndexer()
    {
        if (!$this->_activeIndexer) {
            $indexerName = $this->BConfig->get('modules/Sellvana_CatalogIndex/active_indexer');
            if (empty(static::$_indexers[$indexerName])) {
                throw new BException('Invalid Active Indexer Selection');
            }
            $params = static::$_indexers[$indexerName];
            $this->_activeIndexer = $this->{$params['class']};
        }
        return $this->_activeIndexer;
    }

    public function getAllIndexers()
    {
        $options = [];
        foreach (static::$_indexers as $name => $params) {
            $options[$name] = $params['label'];
        }
        return $options;
    }

    public function generateTestData(array $params = [])
    {
        $params['c'] = isset($params['c']) ? $params['c'] : 9;
        $params['s'] = isset($params['s']) ? $params['s'] : 10;
        $params['p'] = isset($params['p']) ? $params['p'] : 1000;
        $params['r'] = isset($params['r']) ? $params['r'] : true;

        // create categories / subcategories
        if ($params['c']) {
            echo $this->_('<p>Creating categories...</p>');
            /** @var Sellvana_Catalog_Model_Category $root */
            $root = $this->Sellvana_Catalog_Model_Category->load(1);
            for ($i = 1; $i <= $params['c']; $i++) {
                $root->createChild('Category ' . $i);
            }
        }
        if ($params['s']) {
            echo $this->_('<p>Creating subcategories...</p>');
            //$root = $this->Sellvana_Catalog_Model_Category->load(1);
            /** @var Sellvana_Catalog_Model_Category[] $cats */
            $cats = $this->Sellvana_Catalog_Model_Category->orm()->where('parent_id', 1)->find_many();
            foreach ($cats as $c) {
                for ($i = 1; $i <= $params['s']; $i++) {
                    $c->createChild('Subcategory ' . $c->id() . '-' . $i);
                }
            }
        }

        // create products
        $products = [];
        if ($params['p']) {
            echo $this->_('<p>Creating products and inventory...</p>');

            $colors = explode(',', 'White,Yellow,Red,Blue,Cyan,Magenta,Brown,Black,Silver,Gold,Beige,Green,Pink');
            $sizes = explode(',', 'Extra Small,Small,Medium,Large,Extra Large');
            $customFieldsLoaded = $this->BModuleRegistry->isLoaded('Sellvana_CatalogFields');
            if ($customFieldsLoaded) {
                $this->Sellvana_CatalogFields_Main->disable(true);
            }
            $max = $this->Sellvana_Catalog_Model_Product->orm()->select_expr('(max(id))', 'id')->find_one();
            $maxId = $max->id();
            if ($customFieldsLoaded) {
                $this->Sellvana_CatalogFields_Main->disable(false);
                $this->Sellvana_CatalogFields_Model_ProductFieldData->setAutoCreateOptions(true);
            }
//            $categories = $this->Sellvana_Catalog_Model_Category->orm()->where_raw("id_path like '1/%/%'")->select('id')->find_many();
            for ($i = 0; $i < $params['p']; $i++) {
                ++$maxId;
                $cost = rand(1, 1000);
                $basePrice = 'cost+50%';
                $salePrice = 'base-20%';
                $tiers = '5:sale-5%;10:sale-10%';
                $sku = 'test-' . $maxId;
                $name = 'Product ' . $maxId;
                $product = $this->Sellvana_Catalog_Model_Product->create([
                    'product_sku' => $sku,
                    'inventory_sku' => $sku,
                    'product_name' => $name,
                    'short_description' => 'Short Description ' . $maxId,
                    'description' => 'Long Description ' . $maxId,
                    'manage_inventory' => 1,
                    'price.cost' => $cost,
                    'price.base' => $basePrice,
                    'price.sale' => $salePrice,
                    'price.tier' => $tiers,
                    'color' => $colors[rand(0, sizeof($colors)-1)],
                    'size' => $sizes[rand(0, sizeof($sizes)-1)],
                ])->save();
                $inv = $this->Sellvana_Catalog_Model_InventorySku->create([
                    'inventory_sku' => $sku,
                    'title' => $name,
                    'qty_in_stock' => 100,
                    'shipping_weight' => 1,
                ])->save();
                $exists = [];
//                $pId = $product->id;
//                for ($i=0; $i<5; $i++) {
//                    do {
//                        $cId = $categories[rand(0, sizeof($categories)-1)]->id;
//                    } while (!empty($exists[$pId.'-'.$cId]));
//                    $product->addToCategories($cId);
//                    $exists[$pId.'-'.$cId] = true;
//                }
//                $products[] = $product;
            }
        }

        // assign products to categories
        if (true) {
            echo $this->_('<p>Assigning products to categories...</p>');

            $tCategoryProduct = $this->Sellvana_Catalog_Model_CategoryProduct->table();
            $this->BDb->run("TRUNCATE {$tCategoryProduct}");
            $categories = $this->Sellvana_Catalog_Model_Category->orm()->where_raw("id_path like '1/%/%'")
                ->find_many_assoc('id', 'url_path');
            $catIds = array_keys($categories);
            $hlp = $this->Sellvana_Catalog_Model_CategoryProduct;

            $this->Sellvana_CatalogFields_Main->disable(true);
            $this->Sellvana_Catalog_Model_Product->orm()->select('id')->iterate(function($row) use($catIds, $exists, $hlp) {
                $pId = $row->id;
                $exists = [];
                for ($i = 0; $i < 5; $i++) {
                    do {
                        $cId = $catIds[rand(0, sizeof($catIds)-1)];
                    } while (!empty($exists[$pId . '-' . $cId]));
                    $hlp->create(['product_id' => $pId, 'category_id' => $cId])->save();
                    $exists[$pId . '-' . $cId] = true;
                }
            });
            $this->Sellvana_CatalogFields_Main->disable(false);
        }

        // reindex products
        if ($params['r']) {
            echo $this->_('<p>Reindexing...</p>');

            echo "<pre>Starting...\n";
            if ($params['r'] === 2) {
                //$this->Sellvana_CatalogIndex_Main->getIndexer()->indexDropDocs(true);
                $this->Sellvana_CatalogIndex_Model_Doc->update_many(['flag_reindex' => 1]);
            }
            $this->Sellvana_CatalogIndex_Main->getIndexer()->indexPendingProducts()->indexGC();
        }

    }

    public function onCollectActivityItems($args)
    {
        $total = $this->BCache->load('index_progress_total');
        $reIndexed = $this->BCache->load('index_progress_reindexed');
        if ($total > $reIndexed) {
            $args['items'][] = [
                'feed' => 'local',
                'type' => 'progress',
                'group' => 'catalog_indexing',
                'content' => 'Task Running',
                'code' => "catalog_indexing",
            ];
        }
    }

    public function onProductsQuickAdd($args)
    {
        $this->Sellvana_CatalogIndex_Main->getIndexer()->indexProducts($args['products']);
    }
}
