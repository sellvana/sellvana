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
            ->afterTheme('FCom_CatalogIndex_Admin::layout')
        ;
            
        BEvents::i()
            ->on('FCom_Catalog_Model_Product::save.after', 'FCom_CatalogIndex_Admin::onProductSaveAfter')
        ;
    }
    
    static public function layout()
    {
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'admin/header', 'do'=>array(
                        array('addNav', 'catalog/index-fields', array('label'=>'Index Fields', 'href'=>BApp::href('catalogindex/fields'))),
                    ))),
                 '/catalogindex/fields' => array(
                     array('layout', 'base'),
                     array('hook', 'main', 'views'=>array('admin/grid')),
                     array('view', 'admin/header', 'do'=>array(array('setNav', 'catalog/index-fields'))),
                 ),
                 '/catalogindex/fields/form'=>array(
                     array('layout', 'base'),
                     array('layout', 'form'),
                     array('hook', 'main', 'views'=>array('admin/form')),
                     array('view', 'admin/header', 'do'=>array(array('setNav', 'catalog/index-fields'))),
                     array('view', 'admin/form', 'set'=>array(
                         'tab_view_prefix' => 'catalogindex/fields/form/',
                     ), 'do'=>array(
                         array('addTab', 'main', array('label'=>'Index Field', 'pos'=>10)),
                     )),
                 ),
                ));
    }
    
    static public function onProductSaveAfter($args)
    {
        
    }
}