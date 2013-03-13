<?php

class FCom_CatalogIndex extends BClass
{
    protected static $_indexData;
    protected static $_filterValues;

    static public function bootstrap()
    {

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
        foreach ($products as $p) {
            foreach ($fields as $fName=>$field) {
                switch ($field->source_type) {
                case 'field':
                    $fieldName = $field->source_callback ? $field->source_callback : $fName;
                    $value = $p->get($fieldName);
                    break;
                case 'method':
                    $method = $field->source_callback ? $field->source_callback : $fName;
                    $value = $p->$method($field);
                    break;
                case 'callback':
                    $value = BUtil::call($field->source_callback, array($p, $field), true);
                    break;
                default:
                    throw new BException('Invalid source type');
                }
                static::$_indexData[$p->id][$fName] = $value;
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
                foreach ((array)$value as $v) {
                    if (empty(static::$_filterValues[$fId][$v])) {
                        $tuple = array('field_id'=>$fId, 'val'=>$v);
                        $fieldValue = $fieldValueHlp->load($tuple);
                        if (!$fieldValue) {
                            $fieldValue = $fieldValueHlp->create($tuple)->save();
                        }
                        static::$_filterValues[$fId][$v] = $fieldValue->id;
                    }
                    $row = array('doc_id'=>$pId, 'field_id'=>$fId, 'value_id'=>static::$_filterValues[$fId][$v]);
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

    static public function findProducts($search=null, $filters=null, $sort=null)
    {
        // base products ORM object
        $productsOrm = FCom_Catalog_Model_Product::i()->orm('p')
            ->join('FCom_CatalogIndex_Model_Doc', array('d.id','=','p.id'), 'd');

        // retrieve facet field information
        $filterFields = FCom_CatalogIndex_Model_Field::i()->getFields('filter');
        $filterFieldsById = array();
        foreach ($filterFields as $fName=>$field) {
            $filterFieldsById[$field->id] = $field;
        }

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

        $facets = array();
        $filterValues = FCom_CatalogIndex_Model_FieldValue::i()->orm()
            ->where_in('field_id', array_keys($filterFieldsById))->find_many_assoc('id');
        $filterValuesByVal = array();
        foreach ($filterValues as $vId=>$v) {
            $filterValuesByVal[$v->val] = $vId;
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
                        WHERE fv.field_id={$field->id} AND fv.val IN (?)))", $fValues),
                ));
                foreach ($fValues as $v) {
                    unset($filterValues[$filterValuesByVal[$v]]); // don't calculate counts for selected facet values
                    $facets[$field->field_name]['values'][$v]['selected'] = 1;
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
                $field = $filterFieldsById[$c->field_id];
                $value = $filterValues[$c->value_id];
                $facets[$field->field_name]['values'][$value->val]['cnt'] = $c->cnt;
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
            foreach ($counts as $vId=>$cnt) {
                $value = $filterValues[$vId];
                $field = $filterFieldsById[$value->field_id];
                $facets[$field->field_name]['values'][$value->val]['cnt'] = $c->cnt;
            }
        }
        if (BModuleRegistry::isLoaded('FCom_CustomField')) {
            FCom_CustomField_Common::i()->disable(false);
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