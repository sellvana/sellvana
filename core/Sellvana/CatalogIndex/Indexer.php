<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogIndex_Indexer
 *
 * @property Sellvana_CatalogIndex_Main $Sellvana_CatalogIndex_Main
 * @property Sellvana_CatalogIndex_Model_Doc $Sellvana_CatalogIndex_Model_Doc
 * @property Sellvana_CatalogIndex_Model_DocSort $Sellvana_CatalogIndex_Model_DocSort
 * @property Sellvana_CatalogIndex_Model_DocTerm $Sellvana_CatalogIndex_Model_DocTerm
 * @property Sellvana_CatalogIndex_Model_DocValue $Sellvana_CatalogIndex_Model_DocValue
 * @property Sellvana_CatalogIndex_Model_Field $Sellvana_CatalogIndex_Model_Field
 * @property Sellvana_CatalogIndex_Model_FieldValue $Sellvana_CatalogIndex_Model_FieldValue
 * @property Sellvana_CatalogIndex_Model_Term $Sellvana_CatalogIndex_Model_Term
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_CatalogFields_Main $Sellvana_CatalogFields_Main
 * @property Sellvana_CatalogFields_Model_ProductVariant $Sellvana_CatalogFields_Model_ProductVariant
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 */
class Sellvana_CatalogIndex_Indexer extends Sellvana_CatalogIndex_Indexer_Abstract
    implements Sellvana_CatalogIndex_Indexer_Interface
{
    protected function _indexSaveData()
    {
        $this->_indexSaveDocs();
        $this->_indexSaveFilterData();
        $this->_indexSaveSearchData();
        $this->_indexCleanMemory();
    }

    protected function _indexSaveDocs()
    {
        $docHlp = $this->Sellvana_CatalogIndex_Model_Doc;
        $sortHlp = $this->Sellvana_CatalogIndex_Model_DocSort;
        $now = $this->BDb->now();
        $sortFields = $this->Sellvana_CatalogIndex_Model_Field->getFields('sort');
        $sortColumn = [];
        $sortJoin = [];
        foreach ($sortFields as $fName => $field) {
            if ($field->get('sort_method') === 'join') {
                $sortJoin[$fName] = $field;
            } else {
                $sortColumn[$fName] = $field;
            }
        }
        foreach (static::$_indexData as $pId => $pData) {
            $row = ['id' => $pId, 'last_indexed' => $now];

            foreach ($sortColumn as $fName => $field) {
                $row['sort_' . $fName] = substr((string)$pData[$fName], 0, 50);
            }

            $docHlp->create($row)->save();

            foreach ($sortJoin as $fName => $field) {
                if (!isset($pData[$fName])) {
                    continue;
                }
                $row = ['doc_id' => $pId, 'field_id' => $field->id(), 'value' => $pData[$fName]];
                $sortHlp->create($row)->save();
            }
        }
    }

    protected function _indexSaveFilterData()
    {
        $fieldValueHlp = $this->Sellvana_CatalogIndex_Model_FieldValue;
        $docValueHlp = $this->Sellvana_CatalogIndex_Model_DocValue;
        $filterFields = $this->Sellvana_CatalogIndex_Model_Field->getFields('filter');
        foreach (static::$_indexData as $pId => $pData) {
            foreach ($filterFields as $fName => $field) {
                $fId = $field->id();
                $value = !empty($pData[$fName]) ? $pData[$fName] : null;
                if ($value === null || $value === '' || $value === []) {
                    continue;
                }
                foreach ((array)$value as $vKey => $v) {
                    if ($field->get('filter_type') === 'range') {
                        $row = ['doc_id' => $pId, 'field_id' => $fId, 'value_decimal' => $value];
                        $docValueHlp->create($row)->save();
                    } else {
                        $v1 = explode('==>', $v, 2);
                        $vVal = $this->BUtil->simplifyString(trim($v1[0]), '#[^a-z0-9/-]+#');
                        $vDisplay = !empty($v1[1]) ? trim($v1[1]) : $v1[0];
                        if (empty(static::$_filterValues[$fId][$vVal])) {
                            $fieldValue = $fieldValueHlp->loadWhere(['field_id' => (int)$fId, 'val' => (string)$vVal]);
                            if (!$fieldValue) {
                                $fieldValue = $fieldValueHlp->create([
                                    'field_id' => $fId,
                                    'val' => $vVal,
                                    'display' => $vDisplay !== '' ? $vDisplay : null,
                                ])->save();
                            }
                            static::$_filterValues[$fId][$vVal] = $fieldValue->id();
                        }
                        $row = ['doc_id' => $pId, 'field_id' => $fId, 'value_id' => static::$_filterValues[$fId][$vVal]];
                        $docValueHlp->create($row)->save();
                    }
                }
            }
        }
    }

    protected function _indexSaveSearchData()
    {
        $termHlp = $this->Sellvana_CatalogIndex_Model_Term;
        $docTermHlp = $this->Sellvana_CatalogIndex_Model_DocTerm;

        $searchFields = $this->Sellvana_CatalogIndex_Model_Field->getFields('search');
        $allTerms = [];
        foreach (static::$_indexData as $pId => $pData) {
            foreach ($searchFields as $fName => $field) {
                $fId = $field->id();
                $terms = $this->_retrieveTerms($pData[$fName]);
                foreach ($terms as $i => $v) {
                    // index term per product only once
                    if (empty($allTerms[$v][$pId][$fId])) {
                        $allTerms[$v][$pId][$fId] = $i + 1;
                    }
                }
            }
        }
        if ($allTerms) {
            $termIds = $termHlp->orm()->where(['term' => array_keys($allTerms)])->find_many_assoc('term', 'id');
            foreach ($allTerms as $v => $termData) {
                if (empty($termIds[$v])) {
                    $term = $termHlp->create(['term' => $v])->save();
                    $termId = $term->id;
                } else {
                    $termId = $termIds[$v];
                }
                foreach ($termData as $pId => $productData) {
                    foreach ($productData as $fId => $idx) {
                        $row = ['doc_id' => $pId, 'field_id' => $fId, 'term_id' => $termId, 'position' => $idx];
                        $docTermHlp->create($row)->save();
                    }
                }
            }
        }
    }

    public function reindexField($field)
    {
        //TODO: implement 1 field reindexing for all affected products
    }

    public function reindexFieldValue($field, $value)
    {
        //TODO: implement 1 field value reindexing
    }

    public function indexDropDocs($pIds)
    {
        if ($pIds === true) {
            return $this->BDb->run("DELETE FROM " . $this->Sellvana_CatalogIndex_Model_Doc->table());
        } else {
            return $this->Sellvana_CatalogIndex_Model_Doc->delete_many(['id' => $pIds]);
        }
    }

    public function indexGC()
    {
        $tFieldValue = $this->Sellvana_CatalogIndex_Model_FieldValue->table();
        $tDocValue = $this->Sellvana_CatalogIndex_Model_DocValue->table();
        $tTerm = $this->Sellvana_CatalogIndex_Model_Term->table();
        $tDocTerm = $this->Sellvana_CatalogIndex_Model_DocTerm->table();
/*
//TODO: figure out why this doesn't work?? tried raw direct SQL queries as well (5.6.24)
        $this->BDb->run("
DELETE FROM {$tFieldValue} WHERE id NOT IN (SELECT value_id FROM {$tDocValue});
DELETE FROM {$tTerm} WHERE id NOT IN (SELECT term_id FROM {$tDocTerm});
        ");
*/
        $this->BDb->run("
DELETE FROM {$tFieldValue} WHERE NOT EXISTS (SELECT dv.value_id FROM {$tDocValue} dv WHERE dv.value_id={$tFieldValue}.id);
DELETE FROM {$tTerm} WHERE NOT EXISTS (SELECT dt.term_id FROM {$tDocTerm} dt where dt.term_id={$tTerm}.id);
        ");
    }


    /**
     * Search for products, facets and facet counts in index
     *
     * @param array $params
     * @return array ['orm'=>$orm, 'facets'=>$bus['result']['facets']]
     */
    public function searchProducts(array $params = [])
    {
        $bus = $this->_buildBus($params);

        $this->_searchQuery($bus);
        if (!empty($bus['ready'])) {
            return $bus['result'];
        }

        $this->_searchRetrieveFilterFields($bus);
        $this->_searchRetrieveFilterFieldValues($bus);
        $this->_searchApplyFacetFilters($bus);

        if ($this->BModuleRegistry->isLoaded('Sellvana_CatalogFields')) {
            $this->Sellvana_CatalogFields_Main->disable(true);
        }

        $this->_searchCalcFacetValueCounts($bus);

        if ($this->BModuleRegistry->isLoaded('Sellvana_CatalogFields')) {
            $this->Sellvana_CatalogFields_Main->disable(false);
        }

        $this->_searchFormatCategoryFacets($bus);
        $this->_searchSort($bus);

        return $bus['result'];
    }

    protected function _searchQuery(&$bus)
    {
        if (is_null($bus['request']['query'])) {
            $bus['request']['query'] = $bus['request']['http']->get('q');
        }
        if ($bus['request']['query']) {
            $terms = $this->_retrieveTerms($bus['request']['query']);
            //TODO: put weight for `position` in search relevance
            $tDocTerm = $tDocTerm = $this->Sellvana_CatalogIndex_Model_DocTerm->table();
            $orm = $this->Sellvana_CatalogIndex_Model_Term->orm();
            //$orm->where_in('term', $terms);
            $orm->where_raw("term regexp '(" . join('|', $terms) . ")'");
            $termIds = $orm->find_many_assoc('term', 'id');
            if ($termIds) {
                $bus['result']['orm']->where([
                    ["(p.id IN (SELECT dt.doc_id FROM {$tDocTerm} dt WHERE term_id IN (?)))", array_values($termIds)],
                ]);
            } else {
                $bus['result']['orm']->where_raw('0');
                $bus['result']['facets'] = [];
                $bus['ready'] = true;
            }
        }
    }

    protected function _searchRetrieveFilterFields(&$bus)
    {
        $bus['filter']['fields'] = $this->Sellvana_CatalogIndex_Model_Field->getFields('filter');
        $bus['filter']['fields'] = $this->BDb->many_as_array($bus['filter']['fields']);
        $bus['filter']['field_names_by_id'] = [];
        foreach ($bus['filter']['fields'] as $fName => $field) {
            $bus['filter']['field_names_by_id'][$field['id']] = $fName;
            $bus['result']['facets'][$fName] = [// init for sorting
                'display' => $field['field_label'],
                'custom_view' => !empty($field['filter_custom_view']) ? $field['filter_custom_view'] : null,
            ];
            $bus['filter']['fields'][$fName]['values'] = [];
            $bus['filter']['fields'][$fName]['value_ids'] = [];
            // take category filter from options if available
            if (!empty($bus['request']['options']['category']) && $field['field_type'] == 'category') {
                /** @var Sellvana_Catalog_Model_Category $category */
                $category = $bus['request']['options']['category'];
                $bus['request']['filters'][$fName] = $category->get('url_path');
            }
        }
    }

    protected function _searchRetrieveFilterFieldValues(&$bus)
    {
        $bus['filter']['values'] = $this->BDb->many_as_array($this->Sellvana_CatalogIndex_Model_FieldValue->orm()
            ->where_in('field_id', array_keys($bus['filter']['field_names_by_id']))->find_many_assoc('id'));
        $bus['filter']['value_ids_by_val'] = [];
        foreach ($bus['filter']['values'] as $vId => $v) {
            $fName = $bus['filter']['field_names_by_id'][$v['field_id']];
            $field = $bus['filter']['fields'][$fName];
            if ($field['field_type'] == 'category') {
                $lvl = sizeof(explode('/', $v['val']));
                if (empty($bus['request']['filters'][$field['field_name']]) && $lvl > 1) {
                    unset($bus['filter']['values'][$vId]); // show only top level categories if no category selected
                    continue;
                }
                $bus['filter']['values'][$vId]['category_level'] = $lvl;
            }
            $bus['filter']['fields'][$fName]['values'][$vId] = $v;
            $bus['filter']['fields'][$fName]['value_ids'][$vId] = $vId;
            $bus['filter']['value_ids_by_val'][$v['field_id']][$v['val']] = $vId;
        }
    }

    protected function _searchApplyFacetFilters(&$bus)
    {
        $config = $bus['config'];
        /** @var BRequest $req */
        $req = $bus['request']['http'];

        $bus['filter']['facets'] = [];
        //$tFieldValue = $this->Sellvana_CatalogIndex_Model_FieldValue->table();
        $tDocValue = $this->Sellvana_CatalogIndex_Model_DocValue->table();
        foreach ($bus['filter']['fields'] as $fName => $field) {
            if ($field['filter_type'] === 'range') {
                $this->_searchApplyFacetFilters_processRange($bus, $fName, $field, $tDocValue);
                continue;
            }

            $fReqValues = !empty($bus['request']['filters'][$fName]) ? (array)$bus['request']['filters'][$fName] : null;
            if (!empty($fReqValues)) { // request has filter by this field
                $fReqValueIds = [];
                foreach ($fReqValues as $v) {
                    if (empty($bus['filter']['value_ids_by_val'][$field['id']][$v])) {
                        //TODO: error on invalid filter requested value?
                        continue;
                    }
                    $fReqValueIds[] = $bus['filter']['value_ids_by_val'][$field['id']][$v];
                }
                if (empty($fReqValueIds)) {
                    $whereArr = [
                        ['(0)'],
                    ];
                } else {
                    $whereArr = [
                        ["(p.id in (SELECT dv.doc_id from {$tDocValue} dv WHERE dv.value_id IN (?)))", $fReqValueIds],
                    ];
                }
                // order of following actions is important!
                // 1. add filter condition to already created filter ORMs
                foreach ($bus['filter']['facets'] as $ff) {
                    if ($ff['orm'] !== true) {
                        $ff['orm']->where($whereArr);
                    }
                }
                // 2. clone filter facets condition before adding current filter
                if ($field['filter_type'] == 'inclusive' || $field['filter_multivalue']) {
                    $bus['filter']['facets'][$fName] = [
                        'orm' => clone $bus['result']['orm'],
                        'multivalue' => $field['filter_multivalue'],
                        'field_ids' => [$field['id']],
                        'skip_value_ids' => [],
                    ];
                }
                // 3. add filter condition to products ORM
                $bus['result']['orm']->where($whereArr);

                foreach ($fReqValues as $v) {
                    $v = strtolower($v);
                    if (empty($bus['filter']['value_ids_by_val'][$field['id']][$v])) {
                        continue;
                    }
                    $vId = $bus['filter']['value_ids_by_val'][$field['id']][$v];
                    $value = $bus['filter']['values'][$vId];
                    $display = !empty($value['display']) ? $value['display'] : $v;
                    $fName = $field['field_name'];
                    $bus['result']['facets'][$fName]['values'][$v]['display'] = $display;
                    $bus['result']['facets'][$fName]['values'][$v]['selected'] = 1;

                    if ($field['field_type'] == 'category') {
                        $this->_searchApplyFacetFilters_processCategory($bus, $vId, $v, $fName, $value);
                    } else {
                        // don't calculate counts for selected facet values
                        if (!empty($bus['filter']['facets'][$fName])) {
                            $bus['filter']['facets'][$fName]['skip_value_ids'][$vId] = $vId;
                        }
                    }
                }
            } else { // not filtered by this field
                if ($field['filter_multivalue']) {
                    if (empty($bus['filter']['facets']['_multivalue'])) {
                        $bus['filter']['facets']['_multivalue'] = [
                            'orm' => true,
                            'multivalue' => true,
                            'field_ids' => [],
                        ];
                    }
                    $bus['filter']['facets']['_multivalue']['field_ids'][] = $field['id'];
                } else {
                    $bus['filter']['facets'][$fName] = ['orm' => true, 'field_ids' => [$field['id']]];
                }
            }
            $this->_searchApplyFacetFilters_processEmptyValues($bus, $field);
        }
    }

    protected function _searchApplyFacetFilters_processRange(&$bus, $fName, $field, $tDocValue)
    {
        $rangeWhere = [];
        $req = $bus['request']['http'];
        $from = (int)$req->request($fName . '_from');
        $to = (int)$req->request($fName . '_to');
        if ($from) {
            $rangeWhere[] = 'dv.value_decimal >= ' . $from;
        }
        if ($to) {
            $rangeWhere[] = 'dv.value_decimal <= ' . $to;
        }
        if ($rangeWhere) {
            $bus['result']['orm']->where_raw("(p.id in (SELECT dv.doc_id from {$tDocValue} dv WHERE field_id={$field['id']} AND "
                . join(' AND ', $rangeWhere) . '))');
        }
    }

    protected function _searchApplyFacetFilters_processCategory(&$bus, $vId, $v, $fName, $value)
    {
        $valueArr = explode('/', $v);
        $curLevel = sizeof($valueArr);
        $valueParent = join('/', array_slice($valueArr, 0, $curLevel - 1));
        $bus['result']['facets'][$fName]['values'][$v]['level'] = $value['category_level'];
        //$countValueIds = [];
        foreach ($bus['filter']['values'] as $vId1 => $value1) {
            $vVal = $value1['val'];
            if (empty($value1['category_level']) || $vId === $vId1) {
                continue; // skip other fields or same category value
            }
            $showCategory = false;
            $showCount = false;
            $isParent = false;
            if ($value1['category_level'] === $curLevel + 1 && strpos($vVal . '/', $v . '/') === 0) {
                // display and count children
                $showCategory = true;
                $showCount = true;
            } elseif (strpos($v, $vVal . '/') === 0) {
                // display parent categories
                $showCategory = true;
                $isParent = true;
            } elseif (!empty($config['show_root_categories']) && $value1['category_level'] === 1) {
                // display root categories
                $showCategory = true;
                $isParent = true;
                //$showCount = true;
            } elseif (!empty($config['show_sibling_categories'])
                && $value1['category_level'] === $curLevel && strpos($vVal, $valueParent . '/') === 0
            ) {
                // display siblings of current category
                $showCategory = true;
                $showCount = true;
            }
            if ($showCategory) {
                $bus['result']['facets'][$fName]['values'][$vVal]['display'] = $value1['display'];
                $bus['result']['facets'][$fName]['values'][$vVal]['level'] = $value1['category_level'];
                if ($isParent) {
                    $bus['result']['facets'][$fName]['values'][$vVal]['parent'] = 1;
                }
                if ($showCount) {
                    $bus['filter']['facets'][$fName]['count_value_ids'][$vId1] = $vId1;
                }
            }
        }
        if (empty($bus['filter']['facets'][$fName]['count_value_ids'])) {
            $bus['filter']['facets'][$fName]['skip_value_ids'] = true;
        }
    }

    protected function _searchApplyFacetFilters_processEmptyValues(&$bus, $field)
    {
        if ($field['filter_show_empty']) {
            foreach ($field['values'] as $vId => $v) {
                if (empty($bus['result']['facets'][$field['field_name']]['values'][$v['val']])) {
                    $facetValue =& $bus['result']['facets'][$field['field_name']]['values'][$v['val']];
                    $facetValue['display'] = !empty($v['display']) ? $v['display'] : $v['val'];
                    $facetValue['cnt'] = 0;
                }
            }
        }
    }

    protected function _searchCalcFacetValueCounts(&$bus)
    {
        foreach ($bus['filter']['facets'] as $fName => $ff) {
            if (empty($bus['filter']['fields'][$fName])) {
                continue;
            }
            $field = $bus['filter']['fields'][$fName];
            if (!$field['filter_counts']) {
                continue;
            }
            /** @var BORM $orm */
            $orm = $ff['orm'] === true ? clone $bus['result']['orm'] : $ff['orm'];
            $orm->join('Sellvana_CatalogIndex_Model_DocValue', ['dv.doc_id', '=', 'p.id'], 'dv');

            if (!empty($ff['count_value_ids'])) {
                $orm->where_in('dv.value_id', array_values($ff['count_value_ids']));
            } elseif (!empty($ff['skip_value_ids'])) {
                if (true === $ff['skip_value_ids']) {
                    continue;
                } elseif (!empty($bus['filter']['fields'][$fName])) {
                    $includeValueIds = $bus['filter']['fields'][$fName]['value_ids'];
                    $sizeofSkip = !empty($ff['skip_value_ids']) ? sizeof($ff['skip_value_ids']) : 0;
                    $sizeofInclude = !empty($includeValueIds) ? sizeof($includeValueIds) : 0;
                    if ($sizeofSkip == $sizeofInclude) {
                        continue;
                    } elseif ($sizeofSkip > $sizeofInclude / 2) { // slight optimization - inverse filter
                        foreach ($ff['skip_value_ids'] as $vId) {
                            unset($includeValueIds[$vId]);
                        }
                        $orm->where_in('dv.value_id', $includeValueIds);
                    } else {
                        $orm->where_not_in('dv.value_id', array_values($ff['skip_value_ids']));
                    }
                } else {
                    $orm->where_not_in('dv.value_id', array_values($ff['skip_value_ids']));
                }
            }
            if (!empty($ff['multivalue'])) {
                $orm->where_in('dv.field_id', $ff['field_ids']);
                foreach ($ff['field_ids'] as $fId) {
                    $field = $bus['filter']['fields'][$bus['filter']['field_names_by_id'][$fId]];
                    foreach ($field['values'] as $vId => $value) {
                        if (!empty($ff['count_value_ids'])) {
                            if (empty($ff['count_value_ids'][$vId])) {
                                continue;
                            }
                        } elseif (!empty($ff['skip_value_ids'][$vId])) {
                            continue;
                        }
                        $orm->select_expr("(SUM(IF(value_id={$vId},1,0)))", $vId);
                    }
                }
                if ($countsModel = $orm->find_one()) {
                    $counts = $countsModel->as_array();
                    if ($counts) {
                        foreach ($counts as $vId => $cnt) {
                            if (!isset($bus['filter']['values'][$vId]) || !is_array($bus['filter']['values'][$vId])) {
                                continue;
                            }
                            $v = $bus['filter']['values'][$vId];
                            if (!isset($bus['filter']['fields'][$bus['filter']['field_names_by_id'][$v['field_id']]])) {
                                continue;
                            }
                            $f = $bus['filter']['fields'][$bus['filter']['field_names_by_id'][$v['field_id']]];
                            $facetValue =& $bus['result']['facets'][$f['field_name']]['values'][$v['val']];
                            $facetValue['display'] = !empty($v['display']) ? $v['display'] : $v['val'];
                            $facetValue['cnt'] = $cnt;
                        }
                    }
                }
            } else { // TODO: benchmark whether vertical count is faster than horizontal
                //$fName = $bus['filter']['field_names_by_id'][$ff['field_ids'][0]]; // single value fields always separate (1 field per facet query)
                //$field = $bus['filter']['fields'][$fName];
                $counts = $orm->select('dv.value_id')->select_expr('COUNT(*)', 'cnt')
                    ->where_in('dv.field_id', $ff['field_ids']) //TODO: maybe filter by value_id? preferred index conflict?
                    ->group_by('dv.value_id')->find_many();
                /** @var BModel $c */
                foreach ($counts as $c) {
                    $v = $bus['filter']['values'][$c->get('value_id')];
                    $f = $bus['filter']['fields'][$bus['filter']['field_names_by_id'][$v['field_id']]];
                    $facetValue =& $bus['result']['facets'][$f['field_name']]['values'][$v['val']];
                    $facetValue['display'] = $v['display'] ? $v['display'] : $v['val'];
                    $facetValue['cnt'] = $c->get('cnt');
                }
            }
        }
    }

    protected function _searchFormatCategoryFacets(&$bus)
    {
        foreach ($bus['filter']['fields'] as $fName => $field) {
            if (empty($bus['result']['facets'][$field['field_name']]['values'])) {
                $this->BDebug->debug('Empty values for facet field ' . $field['field_name']);
                continue;
            }
            ksort($bus['result']['facets'][$field['field_name']]['values'], SORT_NATURAL | SORT_FLAG_CASE);
            if ($field['field_type'] == 'category') {
                foreach ($bus['result']['facets'][$field['field_name']]['values'] as $vKey => &$fValue) {
                    $vId = $bus['filter']['value_ids_by_val'][$field['id']][$vKey];
                    if (!empty($bus['filter']['values'][$vId])) {
                        $fValue['level'] = $bus['filter']['values'][$vId]['category_level'];
                    }
                }
                unset($fValue);
            }
        }
    }

    protected function _searchSort(&$bus)
    {
        $sort = $bus['request']['sort'];
        /** @var BRequest $req */
        $req = $bus['request']['http'];

        if (is_null($sort)) {
            if (!($sort = trim($req->get('sc')))) {
                $sort = trim($req->get('s') . ' ' . $req->get('sd'));
            }
        }
        if ($sort) {
            list($field, $dir) = is_string($sort) ? explode(' ', $sort) + ['', ''] : $sort;
            $method = 'order_by_' . (strtolower($dir) == 'desc' ? 'desc' : 'asc');

            $bus['result']['orm']->$method($field);
        }
    }
}
