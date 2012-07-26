<?php

class FCom_Test_Admin extends BClass
{
    /**
     * Bootstrap routes, events and layout for Admin part
     */
    static public function bootstrap()
    {
        BFrontController::i()
            ->route('GET /tests/index', 'FCom_Test_Admin_Controller_Tests.index')
            ->route('GET /tests/run', 'FCom_Test_Admin_Controller_Tests.run')
            ->route('GET /tests/run2', 'FCom_Test_Admin_Controller_Tests.run2');

        BLayout::i()->addAllViews('Admin/views')->afterTheme('FCom_Test_Admin::layout');
    }


    /**
     * Itialized base layout, navigation links and page views scripts
     */
    static public function layout()
    {

        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'admin/header', 'do'=>array(
                        array('addNav', 'system/tests', array('label'=>'Tests', 'pos'=>100, 'href'=>BApp::href('tests/index'))),
                    ))),
                    '/tests/index'=>array(
                        array('layout', 'base'),
                        array('hook', 'main', 'views'=>array('tests/index')),
                        array('view', 'admin/header', 'do'=>array(array('setNav', 'test/index')))
                    ),
                    '/settings'=>array(
                        array('view', 'settings', 'do'=>array(
                            array('addTab', 'FCom_Test', array('label'=>'Unit Tests', 'async'=>true))
                        ))
                    ),
                ));
    }
}