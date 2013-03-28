<?php

require_once __DIR__.'/CatalogIndex.php';

class FCom_CatalogIndex_Admin extends BClass
{
    static public function bootstrap()
    {
        FCom_CatalogIndex::bootstrap();
        
        BFrontController::i()
            ->route('GET /index-test', 'FCom_CatalogIndex_Admin_Controller.test')
        ;
        BLayout::i()->addAllViews('Admin/views');
    }
}