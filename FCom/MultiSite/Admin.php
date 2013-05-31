<?php

class FCom_MultiSite_Admin extends BClass
{
    static public function bootstrap()
    {
        BRouting::i()
            ->get('/multisite', 'FCom_MultiSite_Admin_Controller.index')
            ->any('/multisite/.action', 'FCom_MultiSite_Admin_Controller')
        ;

        BLayout::i()
            ->addAllViews('Admin/views')
            ->afterTheme('FCom_MultiSite_Admin::layout')
        ;
    }

    static public function layout()
    {
        BLayout::i()->addLayout(array(
            'base'=>array(
                array('view'=>'admin/header', 'do'=>array(
                    array('addNav', 'system/multisite', array('label'=>'Multi Site', 'pos'=>30,
                        'href'=>BApp::href('multisite'))),
                )),
            ),

            '/multisite'=>array(
                array('layout'=>'base'),
                array('hook'=>'main', 'views'=>array('admin/grid')),
                array('view'=>'admin/header', 'do'=>array(array('setNav', 'system/multisite'))),
            ),
            '/multisite/form'=>array(
                array('layout'=>'base'),
                array('layout'=>'form'),
                array('hook'=>'main', 'views'=>array('admin/form')),
                array('view'=>'admin/header', 'do'=>array(array('setNav', 'system/multisite'))),
                array('view'=>'admin/form', 'set'=>array(
                    'tab_view_prefix' => 'multisite/sites-form/',
                ), 'do'=>array(
                    array('addTab', 'main', array('label'=>'Site Info', 'pos'=>10)),
                )),
            ),
        ));
    }
}
