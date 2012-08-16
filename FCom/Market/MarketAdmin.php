<?php

class FCom_Market_Admin extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_Market_Admin::layout');

        BFrontController::i()
            ->route('GET /market', 'FCom_Market_Admin_Controller.index')
            ->route('GET|POST /market/.action', 'FCom_Market_Admin_Controller')

        ;
    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'admin/header', 'do'=>array(
                    array('addNav', 'market', array('label'=>'Market', 'pos'=>100)),
                    array('addNav', 'market/market', array('label'=>'Market Center', 'href'=>BApp::href('market/market'))),
                    array('addNav', 'market/index', array('label'=>'My modules', 'href'=>BApp::href('market/index'))),
                )),
            ),
            '/market'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('admin/grid')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'market'))),
                ),
             '/market/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('admin/form')),
                    array('view', 'admin/form', 'set'=>array(
                        'tab_view_prefix' => 'market/',
                    ), 'do'=>array(
                        array('addTab', 'main', array('label'=>'Market', 'pos'=>10))
                    )),
             ),
            '/market/market'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('market/market')),
            ),
        ));
    }
}