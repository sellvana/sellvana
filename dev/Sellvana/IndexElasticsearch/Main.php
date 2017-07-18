<?php

class Sellvana_IndexElasticsearch_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_CatalogIndex_Main->addIndexer('elasticsearch', [
            'class' => 'Sellvana_IndexElasticsearch_Indexer',
            'label' => (('Elasticsearch')),
        ]);
    }


}