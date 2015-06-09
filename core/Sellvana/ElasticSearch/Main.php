<?php

class Sellvana_Elasticsearch_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_CatalogIndex_Main->addIndexer('elasticsearch', [
            'class' => 'Sellvana_Elasticsearch_Indexer',
            'label' => 'Elasticsearch',
        ]);
    }


}