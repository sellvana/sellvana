<?php

class FCom_CatalogIndex_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_reindex()
    {
        BResponse::i()->startLongResponse();
        BDebug::mode('PRODUCTION');
        BORM::configure('logging', 0);
        BConfig::i()->set('db/logging', 0);

        echo "<pre>Starting...\n";
        if (BRequest::i()->request('CLEAR')) {
            //FCom_CatalogIndex_Indexer::i()->indexDropDocs(true);
            FCom_CatalogIndex_Model_Doc::i()->update_many(array('flag_reindex'=>1));
        }
        FCom_CatalogIndex_Indexer::i()->indexProducts(true);
        FCom_CatalogIndex_Indexer::i()->indexGC();
        echo 'DONE';
        exit;
    }

}
