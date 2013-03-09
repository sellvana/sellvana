<?php

require_once __DIR__.'/CatalogIndex.php';

class FCom_CatalogIndex_Frontend extends BClass
{
    static public function bootstrap()
    {
        FCom_CatalogIndex::bootstrap();
        
        BFrontController::i()
            ->route('GET /index-test', 'FCom_CatalogIndex_Frontend_Controller.test')
        ;
    }
}