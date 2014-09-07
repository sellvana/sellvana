<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CatalogIndex_Main extends BClass
{
    protected static $_autoReindex = true;
    protected static $_prevAutoReindex;
    protected static $_filterParams;

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
            $this->FCom_CatalogIndex_Indexer->indexProducts([$args['model']]);
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
        $this->FCom_CatalogIndex_Model_Doc->flagReindex($args['product_ids']);
        if (static::$_autoReindex) {
            $this->FCom_CatalogIndex_Indexer->indexProducts(true);
        }
    }

    public function onCategoryAfterSave($args)
    {
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
        $this->FCom_CatalogIndex_Indexer->indexProducts($reindexIds);
    }

    public function onCustomFieldAfterSave($args)
    {
        if (static::$_autoReindex && !$args['model']->isNewRecord()) {
            $indexField = $this->FCom_CatalogIndex_Model_Field->load($args['model']->field_code, 'field_name');
            if ($indexField) {
                //TODO when a edited field is saved, it throws error
                //$this->FCom_CatalogIndex_Indexer->reindexField($indexField);
            }
        }
    }

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'catalog_index' => 'Product Indexing',
        ]);
    }
}
