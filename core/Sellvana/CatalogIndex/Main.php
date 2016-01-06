<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
        $oldValues = $model->old_values();
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

            $this->Sellvana_CatalogIndex_Model_Doc->update_many(['flag_reindex' => 1], ['id' => $reindexIds]);
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
            'settings/Sellvana_CatalogIndex' => BLocale::i()->_('Product Indexing Settings'),
            'catalog_index' => BLocale::i()->_('Product Indexing'),
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
}
