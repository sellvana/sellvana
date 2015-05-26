<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogIndex_Indexer
 *
 * @method static Sellvana_CatalogIndex_Indexer i()
 * @property Sellvana_CatalogIndex_Main $Sellvana_CatalogIndex_Main
 * @property Sellvana_CatalogIndex_Model_Doc $Sellvana_CatalogIndex_Model_Doc
 * @property Sellvana_CatalogIndex_Model_DocSort $Sellvana_CatalogIndex_Model_DocSort
 * @property Sellvana_CatalogIndex_Model_DocTerm $Sellvana_CatalogIndex_Model_DocTerm
 * @property Sellvana_CatalogIndex_Model_DocValue $Sellvana_CatalogIndex_Model_DocValue
 * @property Sellvana_CatalogIndex_Model_Field $Sellvana_CatalogIndex_Model_Field
 * @property Sellvana_CatalogIndex_Model_FieldValue $Sellvana_CatalogIndex_Model_FieldValue
 * @property Sellvana_CatalogIndex_Model_Term $Sellvana_CatalogIndex_Model_Term
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_CustomField_Main $Sellvana_CustomField_Main
 * @property Sellvana_CustomField_Model_ProductVariant $Sellvana_CustomField_Model_ProductVariant
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 */
class Sellvana_CatalogIndex_Indexer extends BClass
{
    protected static $_maxChunkSize = 100;
    protected static $_indexData;
    protected static $_filterValues;
    protected static $_cnt_reindexed;

    public function indexProducts($products)
    {
        if (empty($products)) {
            return;
        }
        /** @var FCom_PushServer_Model_Client $pushClient */
        $pushClient = $this->FCom_PushServer_Model_Client->sessionClient();
        if ($products === true) {
            $i = 0;
            //$start = 0;
            $t = time();
            do {
                $products = $this->Sellvana_Catalog_Model_Product->orm('p')
                    ->left_outer_join('Sellvana_CatalogIndex_Model_Doc', ['idx.id', '=', 'p.id'], 'idx')
                    ->where_complex(['OR' => ['idx.id is null', 'idx.flag_reindex=1']])
                    ->limit(static::$_maxChunkSize)
                    //->offset($start)
                    ->find_many();
                $this->indexProducts($products);
                echo 'DONE CHUNK ' . ($i++) . ': ' . memory_get_usage(true) . ' / ' . memory_get_peak_usage(true)
                    . ' - ' . (time() - $t) . "s\n";
                $t = time();
                //$start += static::$_maxChunkSize;
            } while (sizeof($products) == static::$_maxChunkSize);
            return;
        }

        if (sizeof($products) > static::$_maxChunkSize) {
            $chunks = array_chunk($products, static::$_maxChunkSize);
            foreach ($chunks as $i => $chunk) {
                $this->indexProducts($chunk);
                echo 'DONE CHUNK ' . $i . ': ' . memory_get_usage(true) . ' / ' . memory_get_peak_usage(true) . "\n";
            }
            return;
        }

        $pIds = [];
        $loadIds = [];
        /**
         * @var int $i
         * @var Sellvana_Catalog_Model_Product $p
         */
        foreach ($products as $i => $p) {
            if (is_numeric($p)) {
                $loadIds[$i] = (int)$p;
                $pIds[] = (int)$p;
            } else {
                $pIds[] = $p->id();
            }
        }
        if ($loadIds) {
            $loadProducts = $this->Sellvana_Catalog_Model_Product->orm('p')->where_in('p.id', $loadIds)->find_many_assoc();
            foreach ($loadIds as $i => $p) {
                if (!empty($loadProducts[$p])) {
                    $products[$i] = $loadProducts[$p];
                } else {
                    unset($products[$i]);
                }
            }
        }
        if ($pIds) {
            $this->indexDropDocs($pIds);
        }
        // TODO: Improve filtering out disabled products
        foreach ($products as $i => $p) {
            if ($p->isDisabled()) {
                unset($products[$i]);
            }
        }

        //TODO: for less memory usage chunk the products data
        $this->_indexFetchProductsData($products);
        $this->_indexFetchVariantsData($products);
        static::$_cnt_reindexed += count($products);
        unset($products);
        $this->_indexSaveDocs();
        $this->_indexSaveFilterData();
        $this->_indexSaveSearchData();
        $this->indexCleanMemory();
        $pushClient->send(['channel' => 'index', 'signal' => 'progress', 'reindexed' => static::$_cnt_reindexed]);
    }

    public function indexDropDocs($pIds)
    {
        if ($pIds === true) {
            return $this->BDb->run("DELETE FROM " . $this->Sellvana_CatalogIndex_Model_Doc->table());
        } else {
            return $this->Sellvana_CatalogIndex_Model_Doc->delete_many(['id' => $pIds]);
        }
    }

    protected function _indexFetchProductsData($products)
    {
        $fields = $this->Sellvana_CatalogIndex_Model_Field->getFields();
        static::$_indexData = [];

        foreach ($fields as $fName => $field) {
            $source = $field->source_callback ? $field->source_callback : $fName;
            switch ($field->source_type) {
            case 'field':
                foreach ($products as $p) {
                    static::$_indexData[$p->id()][$fName] = $p->get($source);
                }
                break;
            case 'method':
                foreach ($products as $p) {
                    static::$_indexData[$p->id()][$fName] = $p->$source($field);
                }
                break;
            case 'callback':
                $fieldData = $this->BUtil->call($source, [$products, $field], true);
                foreach ($fieldData as $pId => $value) {
                    static::$_indexData[$pId][$fName] = $value;
                }
                break;
            default:
                throw new BException('Invalid source type');
            }
        }
    }

    protected function _indexFetchVariantsData($products)
    {
        if (!$this->BModuleRegistry->isLoaded('Sellvana_CustomField')) {
            return;
        }
        $pIds = [];
        foreach ($products as $p) {
            $pIds[] = $p->id();
        }
        if (!$pIds) {
            return;
        }
        $variants = $this->Sellvana_CustomField_Model_ProductVariant->orm()
            ->where_in('product_id', $pIds)->find_many();
        if (!$variants) {
            return;
        }
        foreach ($variants as $v) {
            $pId = $v->get('product_id');
            $vFieldValues = $this->BUtil->fromJson($v->get('field_values'));
            $fValues = [];
            foreach ($vFieldValues as $field => $value) {
                if (empty(static::$_indexData[$pId][$field])) {
                    $fValues[$field] = [];
                } else {
                    $fValues[$field] = (array)static::$_indexData[$pId][$field];
                }
                $fValues[$field][] = $value;
            }
            foreach ($fValues as $field => $values) {
                static::$_indexData[$pId][$field] = array_unique($values);
            }
        }
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


    protected function _retrieveTerms($string)
    {
        $string = strtolower(strip_tags($string));
        $string = preg_replace('#[^a-z0-9 \t\n\r]#', '', $string);
        return preg_split('#[ \t\n\r]#', $string, null, PREG_SPLIT_NO_EMPTY);
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

    public function indexCleanMemory($all = false)
    {
        static::$_indexData = null;
        static::$_filterValues = null;
        gc_collect_cycles();
    }

    public function indexGC()
    {
        $tFieldValue = $this->Sellvana_CatalogIndex_Model_FieldValue->table();
        $tDocValue = $this->Sellvana_CatalogIndex_Model_DocValue->table();
        $tTerm = $this->Sellvana_CatalogIndex_Model_Term->table();
        $tDocTerm = $this->Sellvana_CatalogIndex_Model_DocTerm->table();

        $this->BDb->run("
DELETE FROM {$tFieldValue} WHERE id NOT IN (SELECT value_id FROM {$tDocValue});
DELETE FROM {$tTerm} WHERE id NOT IN (SELECT term_id FROM {$tDocTerm});
        ");
    }

    /**
     * Search for products, facets and facet counts in index
     *
     * @param string $search
     * @param array $filters
     * @param string $sort
     * @param array $options
     * @return array ['orm'=>$orm, 'facets'=>$facets]
     */
    public function searchProducts($search = null, $filters = null, $sort = null, $options = [])
    {
        $config = $this->BConfig->get('modules/Sellvana_CatalogIndex');
        if (is_null($filters)) {
            $filters = $this->Sellvana_CatalogIndex_Main->parseUrl();
        }

        // base products ORM object
        $productsOrm = $this->Sellvana_Catalog_Model_Product->orm('p')
            ->join('Sellvana_CatalogIndex_Model_Doc', ['d.id', '=', 'p.id'], 'd');

        $req = $this->BRequest;
        // apply term search

        if (is_null($search)) {
            $search = $req->get('q');
        }
        if ($search) {
            $terms = $this->_retrieveTerms($search);
            //TODO: put weight for `position` in search relevance
            $tDocTerm = $tDocTerm = $this->Sellvana_CatalogIndex_Model_DocTerm->table();
            $orm = $this->Sellvana_CatalogIndex_Model_Term->orm();
            //$orm->where_in('term', $terms);
            $orm->where_raw("term regexp '(" . join('|', $terms) . ")'");
            $termIds = $orm->find_many_assoc('term', 'id');
            if ($termIds) {
                $productsOrm->where([
                    ["(p.id IN (SELECT dt.doc_id FROM {$tDocTerm} dt WHERE term_id IN (?)))", array_values($termIds)],
                ]);
            } else {
                $productsOrm->where_raw('0');
                return ['orm' => $productsOrm, 'facets' => []];
            }
        }

        // result for facet counts
        $facets = [];

        // retrieve facet field information
        $filterFields = $this->BDb->many_as_array($this->Sellvana_CatalogIndex_Model_Field->getFields('filter'));
        $filterFieldNamesById = [];
        foreach ($filterFields as $fName => $field) {
            $filterFieldNamesById[$field['id']] = $fName;
            $facets[$fName] = [// init for sorting
                'display' => $field['field_label'],
                'custom_view' => !empty($field['filter_custom_view']) ? $field['filter_custom_view'] : null,
            ];
            $filterFields[$fName]['values'] = [];
            $filterFields[$fName]['value_ids'] = [];
            // take category filter from options if available
            if (!empty($options['category']) && $field['field_type'] == 'category') {
                $filters[$fName] = $options['category']->get('url_path');
            }
        }

        // retrieve facet field values information
        $filterValues = $this->BDb->many_as_array($this->Sellvana_CatalogIndex_Model_FieldValue->orm()
            ->where_in('field_id', array_keys($filterFieldNamesById))->find_many_assoc('id'));
        $filterValueIdsByVal = [];
        foreach ($filterValues as $vId => $v) {
            $fName = $filterFieldNamesById[$v['field_id']];
            $field = $filterFields[$fName];
            if ($field['field_type'] == 'category') {
                $lvl = sizeof(explode('/', $v['val']));
                if (empty($filters[$field['field_name']]) && $lvl > 1) {
                    unset($filterValues[$vId]); // show only top level categories if no category selected
                    continue;
                }
                $filterValues[$vId]['category_level'] = $lvl;
            }
            $filterFields[$fName]['values'][$vId] = $v;
            $filterFields[$fName]['value_ids'][$vId] = $vId;
            $filterValueIdsByVal[$v['field_id']][$v['val']] = $vId;
        }

        // apply facet filters
        $facetFilters = [];
        //$tFieldValue = $this->Sellvana_CatalogIndex_Model_FieldValue->table();
        $tDocValue = $this->Sellvana_CatalogIndex_Model_DocValue->table();
        foreach ($filterFields as $fName => $field) {
            if ($field['filter_type'] === 'range') {
                $rangeWhere = [];
                $from = (int)$req->request($fName . '_from');
                $to = (int)$req->request($fName . '_to');
                if ($from) {
                    $rangeWhere[] = 'dv.value_decimal >= ' . $from;
                }
                if ($to) {
                    $rangeWhere[] = 'dv.value_decimal <= ' . $to;
                }
                if ($rangeWhere) {
                    $productsOrm->where_raw("(p.id in (SELECT dv.doc_id from {$tDocValue} dv WHERE field_id={$field['id']} AND "
                         . join(' AND ', $rangeWhere) . '))');
                }
                continue;
            }

            $fReqValues = !empty($filters[$fName]) ? (array)$filters[$fName] : null;
            if (!empty($fReqValues)) { // request has filter by this field
                $fReqValueIds = [];
                foreach ($fReqValues as $v) {
                    if (empty($filterValueIdsByVal[$field['id']][$v])) {
                        //TODO: error on invalid filter requested value?
                        continue;
                    }
                    $fReqValueIds[] = $filterValueIdsByVal[$field['id']][$v];
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
                foreach ($facetFilters as $ff) {
                    if ($ff['orm'] !== true) {
                        $ff['orm']->where($whereArr);
                    }
                }
                // 2. clone filter facets condition before adding current filter
                if ($field['filter_type'] == 'inclusive' || $field['filter_multivalue']) {
                    $facetFilters[$fName] = [
                        'orm'        => clone $productsOrm,
                        'multivalue' => $field['filter_multivalue'],
                        'field_ids'  => [$field['id']],
                        'skip_value_ids' => [],
                    ];
                }
                // 3. add filter condition to products ORM
                $productsOrm->where($whereArr);


                foreach ($fReqValues as $v) {
                    $v = strtolower($v);
                    if (empty($filterValueIdsByVal[$field['id']][$v])) {
                        continue;
                    }
                    $vId = $filterValueIdsByVal[$field['id']][$v];
                    $value = $filterValues[$vId];
                    $display = !empty($value['display']) ? $value['display'] : $v;
                    $fName = $field['field_name'];
                    $facets[$fName]['values'][$v]['display'] = $display;
                    $facets[$fName]['values'][$v]['selected'] = 1;

                    if ($field['field_type'] == 'category') {
                        $valueArr = explode('/', $v);
                        $curLevel = sizeof($valueArr);
                        $valueParent = join('/', array_slice($valueArr, 0, $curLevel - 1));
                        $facets[$fName]['values'][$v]['level'] = $value['category_level'];
                        //$countValueIds = [];
                        foreach ($filterValues as $vId1 => $value1) {
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
                                $facets[$fName]['values'][$vVal]['display'] = $value1['display'];
                                $facets[$fName]['values'][$vVal]['level'] = $value1['category_level'];
                                if ($isParent) {
                                    $facets[$fName]['values'][$vVal]['parent'] = 1;
                                }
                                if ($showCount) {
                                    $facetFilters[$fName]['count_value_ids'][$vId1] = $vId1;
                                }
                            }
                        }
                        if (empty($facetFilters[$fName]['count_value_ids'])) {
                            $facetFilters[$fName]['skip_value_ids'] = true;
                        }
                    } else {
                        // don't calculate counts for selected facet values
                        if (!empty($facetFilters[$fName])) {
                            $facetFilters[$fName]['skip_value_ids'][$vId] = $vId;
                        }
                    }
                }
            } else { // not filtered by this field
                if ($field['filter_multivalue']) {
                    if (empty($facetFilters['_multivalue'])) {
                        $facetFilters['_multivalue'] = ['orm' => true, 'multivalue' => true, 'field_ids' => []];
                    }
                    $facetFilters['_multivalue']['field_ids'][] = $field['id'];
                } else {
                    $facetFilters[$fName] = ['orm' => true, 'field_ids' => [$field['id']]];
                }
            }
            if ($field['filter_show_empty']) {
                foreach ($field['values'] as $vId => $v) {
                    if (empty($facets[$field['field_name']]['values'][$v['val']])) {
                        $facets[$field['field_name']]['values'][$v['val']]['display'] = !empty($v['display']) ? $v['display'] : $v['val'];
                        $facets[$field['field_name']]['values'][$v['val']]['cnt'] = 0;
                    }
                }
            }
        }

        if ($this->BModuleRegistry->isLoaded('Sellvana_CustomField')) {
            $this->Sellvana_CustomField_Main->disable(true);
        }

        // calculate facet value counts
        foreach ($facetFilters as $fName => $ff) {
            if (empty($filterFields[$fName])) {
                continue;
            }
            $field = $filterFields[$fName];
            if (!$field['filter_counts']) {
                continue;
            }
            $orm = $ff['orm'] === true ? clone $productsOrm : $ff['orm'];
            $orm->join('Sellvana_CatalogIndex_Model_DocValue', ['dv.doc_id', '=', 'p.id'], 'dv');

            if (!empty($ff['count_value_ids'])) {
                $orm->where_in('dv.value_id', array_values($ff['count_value_ids']));
            } elseif (!empty($ff['skip_value_ids'])) {
                if (true === $ff['skip_value_ids']) {
                    continue;
                } elseif (!empty($filterFields[$fName])) {
                    $includeValueIds = $filterFields[$fName]['value_ids'];
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
                    $field = $filterFields[$filterFieldNamesById[$fId]];
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
                            if (!isset($filterValues[$vId]) || !is_array($filterValues[$vId])) {
                                continue;
                            }
                            $v = $filterValues[$vId];
                            if (!isset($filterFields[$filterFieldNamesById[$v['field_id']]])) {
                                continue;
                            }
                            $f = $filterFields[$filterFieldNamesById[$v['field_id']]];
                            $facets[$f['field_name']]['values'][$v['val']]['display'] = !empty($v['display']) ? $v['display'] : $v['val'];
                            $facets[$f['field_name']]['values'][$v['val']]['cnt'] = $cnt;
                        }
                    }
                }
            } else { // TODO: benchmark whether vertical count is faster than horizontal
                //$fName = $filterFieldNamesById[$ff['field_ids'][0]]; // single value fields always separate (1 field per facet query)
                //$field = $filterFields[$fName];
                $counts = $orm->select('dv.value_id')->select_expr('COUNT(*)', 'cnt')
                    ->where_in('dv.field_id', $ff['field_ids']) //TODO: maybe filter by value_id? preferred index conflict?
                    ->group_by('dv.value_id')->find_many();
                foreach ($counts as $c) {
                    $v = $filterValues[$c->get('value_id')];
                    $f = $filterFields[$filterFieldNamesById[$v['field_id']]];
                    $facets[$f['field_name']]['values'][$v['val']]['display'] = $v['display'] ? $v['display'] : $v['val'];
                    $facets[$f['field_name']]['values'][$v['val']]['cnt'] = $c->get('cnt');
                }
            }
        }

        if ($this->BModuleRegistry->isLoaded('Sellvana_CustomField')) {
            $this->Sellvana_CustomField_Main->disable(false);
        }

        // format categories facet result
        foreach ($filterFields as $fName => $field) {
            if (empty($facets[$field['field_name']]['values'])) {
                $this->BDebug->debug('Empty values for facet field ' . $field['field_name']);
                continue;
            }
            ksort($facets[$field['field_name']]['values'], SORT_NATURAL | SORT_FLAG_CASE);
            if ($field['field_type'] == 'category' && !empty($facets[$field['field_name']]['values'])) {
                foreach ($facets[$field['field_name']]['values'] as $vKey => &$fValue) {
                    $vId = $filterValueIdsByVal[$field['id']][$vKey];
                    if (!empty($filterValues[$vId])) {
                        $fValue['level'] = $filterValues[$vId]['category_level'];
                    }
                }
                unset($value);
            }
        }

        // apply sorting
        if (is_null($sort)) {
            if (!($sort = trim($req->get('sc')))) {
                $sort = trim($req->get('s') . ' ' . $req->get('sd'));
            }
        }
        if ($sort) {
            list($field, $dir) = is_string($sort) ? explode(' ', $sort) + ['', ''] : $sort;
            $method = 'order_by_' . (strtolower($dir) == 'desc' ? 'desc' : 'asc');
            $productsOrm->$method('sort_' . $field);
        }
        return ['orm' => $productsOrm, 'facets' => $facets];
    }
}
