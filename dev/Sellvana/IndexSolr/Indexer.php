<?php

class Sellvana_IndexSolr_Indexer extends Sellvana_CatalogIndex_Indexer_Abstract
    implements Sellvana_CatalogIndex_Indexer_Interface
{
    /** @var Apache_Solr_Service */
    protected $_solr;

    /**
     * Get a client object
     *
     * @return Apache_Solr_Service
     */
    protected function _solr()
    {
        if (!$this->_solr) {
            #$this->BClassAutoload->addPath(__DIR__ . '/lib');
            $conf = $this->BConfig->get('modules/Sellvana_IndexSolr');
            $this->_solr = new Apache_Solr_Service($conf['host'], $conf['port'], $conf['path']);
            if ($this->_solr->ping()) {
                throw new BException('Solr service not responding');
            }
        }
        return $this->_solr;
    }

    public function _indexSaveData()
    {
        $docs = [];
        foreach (static::$_indexData as $id => $data) {
            #$ts = strtotime($data['timestamp']);
            #unset($data['timestamp']);

            $doc = new Apache_Solr_Document();
#            var_dump($data);
            foreach ($data as $k => $v) {

                $doc->setField($k, $v);
            }
            $docs[] = $doc;
        }
        #try {
            $solr = $this->_solr();
            $solr->addDocuments($docs);
            $solr->commit();
            $solr->optimize();
            $this->_indexCleanMemory();
        #} catch (Exception $e) {

        #}
        return $this;
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
        $this->_buildBus($params);

        return $this->_bus['result'];
    }
}