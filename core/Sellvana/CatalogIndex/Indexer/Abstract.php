<?php

/**
 * Class Sellvana_CatalogIndex_Indexer_Abstract
 *
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_CatalogIndex_Main $Sellvana_CatalogIndex_Main
 * @property Sellvana_CatalogIndex_Model_Field $Sellvana_CatalogIndex_Model_Field
 * @property Sellvana_CatalogFields_Model_ProductVariant $Sellvana_CatalogFields_Model_ProductVariant
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_CatalogFields_Model_ProductFieldData $Sellvana_CatalogFields_Model_ProductFieldData
 * @property Sellvana_CatalogIndex_Model_Doc $Sellvana_CatalogIndex_Model_Doc
 */
abstract class Sellvana_CatalogIndex_Indexer_Abstract extends BClass implements Sellvana_CatalogIndex_Indexer_Interface
{

    protected static $_maxChunkSize = 100;
    protected static $_indexData;
    protected static $_filterValues;
    protected static $_cnt_reindexed;
    protected static $_cnt_total;
    protected static $_origClass = __CLASS__;

    /**
     * @var array
     */
    protected $_bus;

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
                //echo 'DONE CHUNK ' . $i . ': ' . memory_get_usage(true) . ' / ' . memory_get_peak_usage(true) . "\n";
            }
            $this->indexGC();
            return;
        }

        try {
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
        } catch(PDOException $e) {
            $this->BDebug->log($e->getMessage(), 'indexer_errors.log', true);
        }
    }

    /**
     * @return BORM
     */
    public function getProductOrm()
    {
        return $this->Sellvana_Catalog_Model_Product->orm('p')->select('p.*')
            ->left_outer_join('Sellvana_CatalogIndex_Model_Doc', ['idx.id', '=', 'p.id'], 'idx');
    }

    /**
     * Find products that are missing from doc table and add them there
     * @return bool
     */
    public function onBeforeIndexPendingProducts()
    {
        $this->BEvents->fire($this->origClass() . '::onBeforeIndexPendingProducts', ['self' => $this]);

        $orm = $this->getProductOrm();
        $orm->where_null('idx.id');

        $now = $this->BDb->now();

        $start = 0;
        do {
            $lostProducts = $orm
                ->offset($start)
                ->limit(static::$_maxChunkSize)
                ->find_many()
            ;
            $start += static::$_maxChunkSize;

            $lostData = [];
            foreach ($lostProducts as $lostProduct) {
                /** @var Sellvana_Catalog_Model_Product $lostProduct */
                if ($lostProduct->isDisabled()){
                    continue;
                }
                $lostData[] = [
                    'id' => $lostProduct->get('id'),
                    'last_indexed' => $now,
                    'flag_reindex' => 1
                ];
            }

            if (!empty($lostData)){
                $this->Sellvana_CatalogIndex_Model_Doc->create_many($lostData);
            }
        } while (sizeof($lostProducts) == static::$_maxChunkSize);

        return true;
    }

    public function indexPendingProducts()
    {
        if (!$this->onBeforeIndexPendingProducts()){
            return $this;
        }

        if ($this->BModuleRegistry->isLoaded('FCom_PushServer')) {
            /** @var FCom_PushServer_Model_Client $pushClient */
            $pushClient = $this->FCom_PushServer_Model_Client->sessionClient();
        } else {
            $pushClient = null;
        }

        $i = 0;
        //$start = 0;
        $t = time();
        $orm = $this->getProductOrm();
        $orm->where('idx.flag_reindex', 1)
            ->where_not_null('idx.id');

        if (empty(static::$_cnt_total)) {
            $count = clone $orm;
            static::$_cnt_total = $count->count();
            $this->BCache->save('index_progress_total', static::$_cnt_total);
            if ($pushClient) {
                $pushClient->send(['channel' => 'index', 'signal' => 'progress', 'total' => static::$_cnt_total]);
            }
        }
        do {
            $limitOrm = clone $orm;
            $products = $limitOrm
                ->limit(static::$_maxChunkSize)
                //->offset($start)
                ->find_many();
            $this->indexProducts($products);
            if (!$this->BRequest->xhr()) {
                echo 'DONE CHUNK ' . ($i++) . ': ' . memory_get_usage(true) . ' / ' . memory_get_peak_usage(true)
                . ' - ' . (time() - $t) . "s\n";
            }
            $t = time();
            //$start += static::$_maxChunkSize;
        } while (sizeof($products) == static::$_maxChunkSize);

        return $this;
    }

    protected function _indexFetchProductsData($products)
    {
        $this->Sellvana_Catalog_Model_ProductPrice->collectProductsPrices($products);

        if ($this->BModuleRegistry->isLoaded('Sellvana_CatalogFields')) {
            $this->Sellvana_CatalogFields_Model_ProductFieldData->collectProductsFieldData($products);
        }

        $fields = $this->Sellvana_CatalogIndex_Model_Field->getFields();
        static::$_indexData = [];

//        foreach ($products as $p) {
//            static::$_indexData[$p->id()]['timestamp'] = $p->get('update_at');
//        }

        foreach ($fields as $fName => $field) {
            $source = $field->get('source_callback') ?: $fName;
            switch ($field->get('source_type')) {
                case 'field':
                    foreach ($products as $p) {
                        static::$_indexData[$p->id()][$fName] = $p->get($source);
                    }
                    break;
                case 'method':
                    foreach ($products as $p) {
                        static::$_indexData[$p->id()][$fName] = $p->{$source}($field);
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
        if (!$this->BModuleRegistry->isLoaded('Sellvana_CatalogFields')) {
            return; // should be always loaded, as it's dep
        }
        $pIds = [];
        foreach ($products as $p) {
            $pIds[] = $p->id();
        }
        if (!$pIds) {
            return;
        }
        $variants = $this->Sellvana_CatalogFields_Model_ProductVariant->orm()
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

    protected function _buildBus(array $params)
    {
        $this->_bus = [
            'request' => [
                'query' => isset($params['query']) ? $params['query'] : null,
                'filters' => isset($params['filters']) ? $params['filters'] : null,
                'sort' => isset($params['sort']) ? $params['sort'] : null,
                'options' => isset($params['options']) ? $params['options'] : [],
                'http' => $this->BRequest,
            ],
            'config' => $this->BConfig->get('modules/Sellvana_CatalogIndex'),
            'result' => [
                'orm' => $this->Sellvana_Catalog_Model_Product->orm('p', 'catalog_products')
                    ->join('Sellvana_CatalogIndex_Model_Doc', ['d.id', '=', 'p.id'], 'd'),
                'facets' => [],
            ],
        ];

        if (is_null($this->_bus['request']['filters'])) {
            $this->_bus['request']['filters'] = $this->Sellvana_CatalogIndex_Main->parseUrl();
        }
    }

}
