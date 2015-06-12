<?php

/**
 * Class Sellvana_CatalogIndex_Indexer_Abstract
 *
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_CatalogIndex_Indexer_Abstract extends BClass
{

    protected static $_maxChunkSize = 100;
    protected static $_indexData;
    protected static $_filterValues;
    protected static $_cnt_reindexed;
    protected static $_cnt_total;

    public function indexProducts($products)
    {
        if (empty($products)) {
            return;
        }
        if ($this->BModuleRegistry->isLoaded('FCom_PushServer')) {
            /** @var FCom_PushServer_Model_Client $pushClient */
            $pushClient = $this->FCom_PushServer_Model_Client->sessionClient();
        } else {
            $pushClient = null;
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

        $this->_indexSaveData();

        $pushClient->send(['channel' => 'index', 'signal' => 'progress', 'reindexed' => static::$_cnt_reindexed]);
        $this->BCache->save('index_progress_reindexed', static::$_cnt_reindexed);
    }

    public function indexPendingProducts()
    {
        if ($this->BModuleRegistry->isLoaded('FCom_PushServer')) {
            /** @var FCom_PushServer_Model_Client $pushClient */
            $pushClient = $this->FCom_PushServer_Model_Client->sessionClient();
        } else {
            $pushClient = null;
        }

        $i = 0;
        //$start = 0;
        $t = time();
        $orm = $this->Sellvana_Catalog_Model_Product
            ->orm('p')->left_outer_join('Sellvana_CatalogIndex_Model_Doc', ['idx.id', '=', 'p.id'], 'idx')
            ->where_complex(['OR' => ['idx.id is null', 'idx.flag_reindex=1']]);
        if (empty(static::$_cnt_total)) {
            $count = clone $orm;
            static::$_cnt_total = $count->count();
            $this->BCache->save('index_progress_total', static::$_cnt_total);
            if ($pushClient) {
                $pushClient->send(['channel' => 'index', 'signal' => 'progress', 'total' => static::$_cnt_total]);
            }
        }
        do {
            $products = $orm
                ->limit(static::$_maxChunkSize)
                //->offset($start)
                ->find_many();
            $this->indexProducts($products);
            echo 'DONE CHUNK ' . ($i++) . ': ' . memory_get_usage(true) . ' / ' . memory_get_peak_usage(true)
                . ' - ' . (time() - $t) . "s\n";
            $t = time();
            //$start += static::$_maxChunkSize;
        } while (sizeof($products) == static::$_maxChunkSize);

        return $this;
    }

    protected function _indexFetchProductsData($products)
    {
        $fields = $this->Sellvana_CatalogIndex_Model_Field->getFields();
        static::$_indexData = [];

//        foreach ($products as $p) {
//            static::$_indexData[$p->id()]['timestamp'] = $p->get('update_at');
//        }

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

    protected function _indexCleanMemory($all = false)
    {
        static::$_indexData = null;
        static::$_filterValues = null;
        gc_collect_cycles();
    }

    protected function _retrieveTerms($string)
    {
        $string = strtolower(strip_tags($string));
        $string = preg_replace('#[^a-z0-9 \t\n\r]#', '', $string);
        return preg_split('#[ \t\n\r]#', $string, null, PREG_SPLIT_NO_EMPTY);
    }

    protected function _buildBus()
    {
        $bus = [
            'request' => [
                'query' => isset($params['query']) ? $params['query'] : null,
                'filters' => isset($params['filters']) ? $params['filters'] : null,
                'sort' => isset($params['sort']) ? $params['sort'] : null,
                'options' => isset($params['options']) ? $params['options'] : [],
                'http' => $this->BRequest,
            ],
            'config' => $this->BConfig->get('modules/Sellvana_CatalogIndex'),
            'result' => [
                'orm' => $this->Sellvana_Catalog_Model_Product->orm('p')
                    ->join('Sellvana_CatalogIndex_Model_Doc', ['d.id', '=', 'p.id'], 'd'),
                'facets' => [],
            ],
        ];

        if (is_null($bus['request']['filters'])) {
            $bus['request']['filters'] = $this->Sellvana_CatalogIndex_Main->parseUrl();
        }

        return $bus;
    }

}