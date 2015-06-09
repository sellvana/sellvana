<?php

class Sellvana_Elasticsearch_Indexer extends Sellvana_CatalogIndex_Indexer_Abstract
    implements Sellvana_CatalogIndex_Indexer_Interface
{
//
//    /** @var \Elasticsearch\Client */
//    protected $_client;
//
//    /** @var array */
//    protected $_indexName;
//
//    /** @var \Elasticsearch\Endpoints\Index */
//    protected $_index;
//
//    /**
//     * Get a client object
//     *
//     * @return \Elasticsearch\Client
//     */
//    protected function _getClient()
//    {
//        if (!$this->_client) {
//            $this->BClassAutoload->addPath(__DIR__ . '/lib');
//            $clientParams = $this->BConfig->get('modules/Sellvana_Elasticsearch/client_params', []);
//            $this->_client = new Elasticsearch\Client($clientParams);
//        }
//        return $this->_client;
//    }
//
//    /**
//     * Get or create default index
//     *
//     * @return \Elasticsearch\Endpoints\Index
//     */
//    protected function _getIndexName()
//    {
//        if (!$this->_indexName) {
//            $this->_indexName = $this->BConfig->get('modules/Sellvana_Elasticsearch/index_name');
//            if (!$this->_indexName) {
//                $this->_indexName = $this->BUtil->randomString(32);
//                $this->BConfig->set('modules/Sellvana_ElasticSearch/index_name', $this->_indexName);
//                $this->BConfig->writeConfigFiles('local');
//            }
//            $indexParams = $this->BConfig->get('modules/Sellvana_Elasticsearch/index_params');
//            $indexParams['index'] = $this->_indexName;
//            $this->_client->indices()->create($indexParams);
//        }
//        return $this->_indexName;
//    }

    public function _indexSaveData()
    {
        //$bulk = [];
        #var_dump($data); exit;
        #$client = $this->_getClient();
        #$indexName = $this->_getIndexName();
        foreach (static::$_indexData as $id => $data) {
            $ts = strtotime($data['timestamp']);
            unset($data['timestamp']);
            $doc = [
                #'index' => $indexName,
                'type' => 'product',
                'id' => $id,
                'timestamp' => $ts,
                'body' => $data,
            ];
            try {
                #$client->index($doc);
            } catch (Exception $e) {
                var_dump($e);
                var_dump($doc);
                exit;
            }
        }
        // $this->_getClient()->bulk($bulk); //TODO: implement bulk indexing
        $this->_indexCleanMemory();
        return $this;
    }

    public function indexPendingProducts()
    {

    }

    public function indexDropDocs($pIds)
    {

    }

    public function reindexField($field)
    {

    }

    public function reindexFieldValue($field, $value)
    {

    }

    public function indexGC()
    {

    }

    public function searchProducts(array $params = [])
    {
        $bus = $this->_buildBus($params);

        return $bus['result'];
    }
}