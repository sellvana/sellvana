<?php

class FCom_CatalogIndex_Admin extends BClass
{
    static public function bootstrap()
    {
        FCom_CatalogIndex_Main::bootstrap();
        
        BRouting::i()
            ->get('/catalogindex/fields', 'FCom_CatalogIndex_Admin_Controller_Fields.index')
            ->any('/catalogindex/fields/.action', 'FCom_CatalogIndex_Admin_Controller_Fields')
        ;
        
        BLayout::i()
            ->addAllViews('Admin/views')
            ->loadLayoutAfterTheme('Admin/layout.yml')
        ;
            
        BEvents::i()
            ->on('FCom_Catalog_Model_Product::save.after', 'FCom_CatalogIndex_Admin::onProductSaveAfter')
        ;
    }
    
    static public function onProductSaveAfter($args)
    {
        
    }
}