<?php

class FCom_CatalogIndex extends BClass
{
    protected static $_filterParams;
    protected static $_indexData;
    protected static $_filterValues;

    static public function bootstrap()
    {
        static::parseUrl();
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

    static public function indexProducts($products)
    {
        if ($products===true) {
            static::indexDropDocs(true);
            $products = FCom_Catalog_Model_Product::i()->orm()->find_many();
        } else {
            $pIds = array();
            foreach ($products as $p) {
                $pIds[] = $p->id;
            }
            static::indexDropDocs($pIds);
        }

        //TODO: for less memory usage chunk the products data
        static::_indexFetchProductsData($products);
        unset($products);
        static::_indexSaveDocs();
        static::_indexSaveFilterData();
        static::_indexSaveSearchData();
        static::indexCleanMemory();
    }

    static public function indexDropDocs($pIds)
    {
        if ($pIds===true) {
            return BDb::run("DELETE FROM ".FCom_CatalogIndex_Model_Doc::table());
        } else {
            return FCom_CatalogIndex_Model_Doc::i()->delete_many($pIds);
        }
    }

    static protected function _indexFetchProductsData($products)
    {
        $fields = FCom_CatalogIndex_Model_Field::i()->getFields();
        static::$_indexData = array();

        foreach ($fields as $fName=>$field) {
            $source = $field->source_callback ? $field->source_callback : $fName;
            switch ($field->source_type) {
            case 'field':
                foreach ($products as $p) {
                    static::$_indexData[$p->id][$fName] = $p->get($source);
                }
                break;
            case 'method':
                foreach ($products as $p) {
                    static::$_indexData[$p->id][$fName] = $p->$source($field);
                }
                break;
            case 'callback':
                $fieldData = BUtil::call($source, array($products, $field), true);
                foreach ($fieldData as $pId=>$value) {
                    static::$_indexData[$pId][$fName] = $value;
                }
                break;
            default:
                throw new BException('Invalid source type');
            }
        }
    }

    static protected function _indexSaveDocs()
    {
        $docHlp = FCom_CatalogIndex_Model_Doc::i();
        $now = BDB::now();
        $sortFields = FCom_CatalogIndex_Model_Field::i()->getFields('sort');
        foreach (static::$_indexData as $pId=>$pData) {
            $row = array('id'=>$pId, 'last_indexed'=>$now);
            foreach ($sortFields as $fName=>$field) {
                $row['sort_'.$fName] = $pData[$fName];
            }
            $docHlp->create($row)->save();
        }
    }

    static protected function _indexSaveFilterData()
    {
        $fieldValueHlp = FCom_CatalogIndex_Model_FieldValue::i();
        $docValueHlp = FCom_CatalogIndex_Model_DocValue::i();
        $filterFields = FCom_CatalogIndex_Model_Field::i()->getFields('filter');
        foreach (static::$_indexData as $pId=>$pData) {
            foreach ($filterFields as $fName=>$field) {
                $fId = $field->id;
                $value = $pData[$fName];
                if (is_null($value) || $value==='' || $value===array()) {
                    continue;
                }
                foreach ((array)$value as $vKey=>$v) {
                    $v1 = explode('==>', $v, 2);
                    $vVal = strtolower(trim($v1[0]));
                    $vDisplay = !empty($v1[1]) ? trim($v1[1]) : $v1[0];
                    if (empty(static::$_filterValues[$fId][$vVal])) {
                        $fieldValue = $fieldValueHlp->load(array('field_id'=>$fId, 'val'=>$vVal));
                        if (!$fieldValue) {
                            $fieldValue = $fieldValueHlp->create(array(
                                'field_id'=>$fId,
                                'val' => $vVal,
                                'display' => $vDisplay!=='' ? $vDisplay : null,
                            ))->save();
                        }
                        static::$_filterValues[$fId][$vVal] = $fieldValue->id;
                    }
                    $row = array('doc_id'=>$pId, 'field_id'=>$fId, 'value_id'=>static::$_filterValues[$fId][$vVal]);
                    $docValueHlp->create($row)->save();
                }
            }
        }
    }


    static protected function _retrieveTerms($string)
    {
        $string = strtolower(strip_tags($string));
        $string = preg_replace('#[^a-z0-9 \t\n\r]#', '', $string);
        return preg_split('#[ \t\n\r]#', $string, null, PREG_SPLIT_NO_EMPTY);
    }

    static protected function _indexSaveSearchData()
    {
        $termHlp = FCom_CatalogIndex_Model_Term::i();
        $docTermHlp = FCom_CatalogIndex_Model_DocTerm::i();

        $searchFields = FCom_CatalogIndex_Model_Field::i()->getFields('search');
        $allTerms = array();
        foreach (static::$_indexData as $pId=>$pData) {
            foreach ($searchFields as $fName=>$field) {
                $fId = $field->id;
                $terms = static::_retrieveTerms($pData[$fName]);
                foreach ($terms as $i=>$v) {
                    // index term per product only once
                    if (empty($allTerms[$v][$pId][$fId])) {
                        $allTerms[$v][$pId][$fId] = $i+1;
                    }
                }
            }
        }
        $termIds = $termHlp->orm()->where(array('term'=>array_keys($allTerms)))->find_many_assoc('term', 'id');
        foreach ($allTerms as $v=>$termData) {
            if (empty($termIds[$v])) {
                $term = $termHlp->create(array('term'=>$v))->save();
                $termId = $term->id;
            } else {
                $termId = $termIds[$v];
            }
            foreach ($termData as $pId=>$productData) {
                foreach ($productData as $fId=>$idx) {
                    $row = array('doc_id'=>$pId, 'field_id'=>$fId, 'term_id'=>$termId, 'position'=>$idx);
                    $docTermHlp->create($row)->save();
                }
            }
        }
    }

    static public function reindexField($field)
    {
        //TODO: implement 1 field reindexing for all affected products
    }

    static public function indexCleanMemory($all=false)
    {
        static::$_indexData = null;
        static::$_filterValues = null;
    }

    static public function indexGC()
    {
        $tFieldValue = FCom_CatalogIndex_Model_FieldValue::table();
        $tDocValue = FCom_CatalogIndex_Model_DocValue::table();
        $tTerm = FCom_CatalogIndex_Model_Term::table();
        $tDocTerm = FCom_CatalogIndex_Model_DocTerm::table();

        BDb::run("
DELETE FROM {$tFieldValue} WHERE id NOT IN (SELECT value_id FROM {$tDocValue});
DELETE FROM {$tTerm} WHERE id NOT IN (SELECT term_id FROM {$tDocTerm});
        ");
    }

    static public function findProducts($search=null, $filters=null, $sort=null, $options=array())
    {
        // base products ORM object
        $productsOrm = FCom_Catalog_Model_Product::i()->orm('p')
            ->join('FCom_CatalogIndex_Model_Doc', array('d.id','=','p.id'), 'd');

        // apply term search
        if ($search) {
            $terms = static::_retrieveTerms($search);
            //TODO: put weight for `position` in search relevance
            $tDocTerm = $tDocTerm = FCom_CatalogIndex_Model_DocTerm::table();
            $tTerm = FCom_CatalogIndex_Model_Term::table();
            $productsOrm->where(array(
                array("(p.id IN (SELECT dt.doc_id FROM {$tDocTerm} dt INNER JOIN {$tTerm} t ON dt.term_id=t.id
                    WHERE t.term IN (?)))", $terms),
            ));
        }

        if (is_null($filters)) {
            $filters = static::$_filterParams;
        }

        $facets = array();

        // retrieve facet field information
        $filterFields = FCom_CatalogIndex_Model_Field::i()->getFields('filter');
        $filterFieldsById = array();
        foreach ($filterFields as $fName=>$field) {
            $filterFieldsById[$field->id] = $field;
            $facets[$fName] = array(
                'display' => $field->field_label,
                'custom_view' => $field->filter_custom_view ? $field->filter_custom_view : null,
            ); // init for sorting
            if (!empty($options['category']) && $field->field_type=='category') {
                $filters[$fName] = $options['category']->url_path;
            }
        }

        $filterValues = FCom_CatalogIndex_Model_FieldValue::i()->orm()
            ->where_in('field_id', array_keys($filterFieldsById))->find_many_assoc('id');
        $filterValuesByVal = array();
        foreach ($filterValues as $vId=>$v) {
            $field = $filterFieldsById[$v->field_id];
            if ($field->field_type == 'category') {
                $lvl = sizeof(explode('/', $v->val));
                if (empty($filters[$field->field_name]) && $lvl > 1) {
                    unset($filterValues[$vId]); // show only top level categories if no category selected
                    continue;
                }
                $v->category_level = $lvl;
            }
            // $v->field = $field;
            $filterValuesByVal[$v->field_id][$v->val] = $vId;
        }

        // apply facet filters
        if ($filters) {
            $where = array();
            $tFieldValue = FCom_CatalogIndex_Model_FieldValue::table();
            $tDocValue = FCom_CatalogIndex_Model_DocValue::table();
            $valueWhere = array();
            $valueParams = array();
            foreach ($filters as $fName=>$fValues) {
                if (empty($filterFields[$fName]) || $filterFields[$fName]->filter_type=='none') {
                    //TODO: throw error?
                    BDebug::warning('Invalid filter field: '.$fName);
                    continue;
                }
                $field = $filterFields[$fName];
                $fValues = (array)$fValues;
                $productsOrm->where(array(
                    array("(p.id in (SELECT dv.doc_id from {$tDocValue} dv INNER JOIN {$tFieldValue} fv ON dv.value_id=fv.id
                        WHERE fv.field_id={$field->id} AND fv.val IN (?)))", array_values($fValues)),
                ));
                foreach ($fValues as $v) {
                    $v = strtolower($v);
                    $vId = $filterValuesByVal[$field->id][$v];
                    $value = $filterValues[$vId];
                    $display = $value->display ? $value->display : $v;
                    $fName = $field->field_name;
                    $facets[$fName]['values'][$v]['display'] = $display;
                    $facets[$fName]['values'][$v]['selected'] = 1;
                    unset($filterValues[$vId]); // don't calculate counts for selected facet values

                    if ($field->field_type=='category') {
                        $curLevel = sizeof(explode('/', $v));
                        $facets[$fName]['values'][$v]['level'] = $value->category_level;
                        foreach ($filterValues as $vId1=>$value1) {
                            $vVal = $value1->val;
                            if (!$value1->category_level || $vId === $vId1) {
                                continue; // skip other fields or same category value
                            }
                            if ($value1->category_level > $curLevel + 1) { // grand-children - don't show at all, TODO: configuration?
                                unset($filterValues[$value1->id]);
                            } elseif (strpos($v, $vVal.'/')===0) { // parent categories
                                $facets[$fName]['values'][$vVal]['display'] = $value1->display;
                                $facets[$fName]['values'][$vVal]['parent'] = 1;
                                $facets[$fName]['values'][$vVal]['level'] = $value1->category_level;
                                unset($filterValues[$value1->id]); // don't calculate counts for selected facet values
                            } elseif (strpos($vVal.'/', $v.'/')!==0) { // lower level categories outside of current
                                unset($filterValues[$value1->id]);
                            }
                        }
                    }
                }
            }
        }

        // calculate facet counts
        $singleValueIds = array();
        $multiValueIds = array();
        // set empty value counts where needed
        foreach ($filterValues as $vId=>$v) {
            $field = $filterFieldsById[$v->field_id];
            if ($field->filter_show_empty) {
                $facets[$field->field_name]['values'][$v->val]['display'] = $v->display ? $v->display : $v->val;
                $facets[$field->field_name]['values'][$v->val]['cnt'] = 0;
            }
            if (!$field->filter_multivalue) {
                $singleValueIds[$v->id] = $v->id;
            } else {
                $multiValueIds[$v->id] = $v->id;
            }
        }

        if (BModuleRegistry::isLoaded('FCom_CustomField')) {
            FCom_CustomField_Common::i()->disable(true);
        }
        $singleFacetOrm = clone $productsOrm;
        $singleFacetOrm->join('FCom_CatalogIndex_Model_DocValue', array('dv.doc_id','=','p.id'), 'dv');
        $multiFacetOrm = clone $singleFacetOrm;
        // count all single value fields counts vertically, should be faster than horizontally (TODO:test benchmarks)
        if (!empty($singleValueIds)) {
            $counts = $singleFacetOrm->select('dv.field_id')->select('dv.value_id')->select_expr('COUNT(*)', 'cnt')
                ->where_in('dv.value_id', $singleValueIds) //TODO: maybe filter by field_id? preferred index conflict?
                ->group_by('dv.value_id')->find_many();
            foreach ($counts as $c) {
                $v = $filterValues[$c->value_id];
                $f = $filterFieldsById[$c->field_id];
                $facets[$f->field_name]['values'][$v->val]['display'] = $v->display ? $v->display : $v->val;
                $facets[$f->field_name]['values'][$v->val]['cnt'] = $c->cnt;
            }
        }
        // count all multivalue field counts horizontally (can be used also for single value)
        if (!empty($multiValueIds)) {
            $multiFacetOrm->where_in('dv.value_id', $multiValueIds);
            foreach ($multiValueIds as $vId) {
                $value = $filterValues[$vId];
                $multiFacetOrm->select_expr("(SUM(IF(value_id={$vId},1,0)))", $vId);
            }
            $counts = $multiFacetOrm->find_one()->as_array();
            if ($counts) {
                foreach ($counts as $vId=>$cnt) {
                    $v = $filterValues[$vId];
                    $f = $filterFieldsById[$v->field_id];
                    $facets[$f->field_name]['values'][$v->val]['display'] = $v->display ? $v->display : $v->val;
                    $facets[$f->field_name]['values'][$v->val]['cnt'] = $cnt;
                }
            }
        }
        if (BModuleRegistry::isLoaded('FCom_CustomField')) {
            FCom_CustomField_Common::i()->disable(false);
        }

        foreach ($filterFields as $fName=>$field) {
            if ($field->field_type=='category') {
                ksort($facets[$field->field_name]['values']);
                foreach ($facets[$field->field_name]['values'] as $vKey=>&$fValue) {
                    $vId = $filterValuesByVal[$field->id][$vKey];
                    if (!empty($filterValues[$vId])) {
                        $fValue['level'] = $filterValues[$vId]->category_level;
                    }
                }
                unset($value);
            }
        }

        // apply sorting
        if ($sort) {
            list($field, $dir) = is_string($sort) ? explode(' ', $sort)+array('','') : $sort;
            $method = 'order_by_'.(strtolower($dir)=='desc' ? 'desc' : 'asc');
            $productsOrm->$method('sort_'.$field);
        }
        return array('orm'=>$productsOrm, 'facets'=>$facets);
    }
}