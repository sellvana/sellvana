<?php

class FCom_CatalogIndex_Main extends BClass
{
    protected static $_autoReindex = true;
    protected static $_filterParams;

    static public function autoReindex($flag)
    {
        static::$_autoReindex = $flag;
    }

    static public function parseUrl()
    {
        if (($getFilters = BRequest::i()->get('filters'))) {
            $getFiltersArr = explode('.', $getFilters);
            static::$_filterParams = array();
            foreach ($getFiltersArr as $filterStr) {
                if ($filterStr==='') {
                    continue;
                }
                $filterArr = explode('-', $filterStr, 2);
                if (empty($filterArr[1])) {
                    continue;
                }
                $valueArr = explode(' ', $filterArr[1]);
                foreach ($valueArr as $v) {
                    if ($v==='') {
                        continue;
                    }
                    static::$_filterParams[$filterArr[0]][$v] = $v;
                }
            }
        }
        return static::$_filterParams;
    }

    static public function getUrl($add=array(), $remove=array())
    {
        $filters = array();
        $params = static::$_filterParams;
        if ($add) {
            foreach ($add as $fKey=>$fValues) {
                foreach ((array)$fValues as $v) {
                    $params[$fKey][$v] = $v;
                }
            }
        }
        if ($remove) {
            foreach ($remove as $fKey=>$fValues) {
                foreach ((array)$fValues as $v) {
                    unset($params[$fKey][$v]);
                }
            }
        }
        foreach ($params as $fKey=>$fValues) {
            if ($fValues) {
                $filters[] = $fKey.'-'.join(' ', (array)$fValues);
            }
        }
        return BUtil::setUrlQuery(BRequest::currentUrl(), array('filters'=>join('.', $filters)));
    }


    static public function onProductAfterSave($args)
    {
        if (static::$_autoReindex) {
            FCom_CatalogIndex_Indexer::i()->indexProducts(array($args['model']));
        }
    }

    static public function onCategoryAfterSave($args)
    {
        $cat = $args['model'];
        $addIds = explode(',', $cat->get('product_ids_add'));
        $removeIds = explode(',', $cat->get('product_ids_remove'));
        $reindexIds = array();
        if (sizeof($addIds)>0 && $addIds[0] != '') {
            $reindexIds += $addIds;
        }
        if (sizeof($removeIds)>0 && $removeIds[0] != '') {
            $reindexIds += $removeIds;
        }
        FCom_CatalogIndex_Indexer::i()->indexProducts($reindexIds);
    }

    static public function onCustomFieldAfterSave($args)
    {
        if (static::$_autoReindex && !$args['model']->isNewRecord()) {
            $indexField = FCom_CatalogIndex_Model_Field::i()->load($args['model']->field_code, 'field_name');
            if ($indexField) {
                //TODO when a edited field is saved, it throws error
                //FCom_CatalogIndex_Indexer::i()->reindexField($indexField);
            }
        }
    }
}
