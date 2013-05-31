<?php

class FCom_Translation_Admin extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
        BEvents::i()->on('BLayout::theme.load.after', 'FCom_Translation_Admin::layout');

        BRouting::i()
            ->get('/translations', 'FCom_Translation_Admin_Controller.index')
            ->any('/translations/.action', 'FCom_Translation_Admin_Controller')
        ;
    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'admin/header', 'do'=>array(
                    array('addNav', 'system/translation', array('label'=>'Translations',
                        'href'=>BApp::href('translations'))),
                )),
            ),
            '/translations'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('admin/grid')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'translations'))),
                ),
             '/translations/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('admin/form')),
                    array('view', 'admin/form', 'set'=>array(
                        'tab_view_prefix' => 'translations/',
                    ), 'do'=>array(
                        array('addTab', 'main', array('label'=>'Translations', 'pos'=>10))
                    )),
             ),
        ));
    }
}