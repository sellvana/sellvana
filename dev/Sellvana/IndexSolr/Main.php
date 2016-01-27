<?php

class Sellvana_IndexSolr_Main extends BClass
{
    public function bootstrap()
    {
        $this->Sellvana_CatalogIndex_Main->addIndexer('solr', [
            'class' => 'Sellvana_IndexSolr_Indexer',
            'label' => 'Solr',
        ]);
    }


}