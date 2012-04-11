<?php

class FCom_Cms_Admin extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Cms_Admin::layout')
        ;

        BFrontController::i()
            ->route('GET /cms/nav', 'FCom_Cms_Admin_Controller_Nav.index')
            ->route('GET|POST /cms/nav/tree_data', 'FCom_Cms_Admin_Controller_Nav.tree_data')
            ->route('GET|POST /cms/nav/tree_form/:id', 'FCom_Cms_Admin_Controller_Nav.tree_form')

            ->route('GET /cms/pages', 'FCom_Cms_Admin_Controller_Pages.index')
            ->route('GET|POST /cms/pages/grid_data', 'FCom_Cms_Admin_Controller_Pages.grid_data')
            ->route('GET|POST /cms/pages/form/:id', 'FCom_Cms_Admin_Controller_Pages.form')
            ->route('GET|POST /cms/pages/history/:id/grid_data', 'FCom_Cms_Admin_Controller_Pages.history_grid_data')

            ->route('GET /cms/blocks', 'FCom_Cms_Admin_Controller_Blocks.index')
            ->route('GET|POST /cms/blocks/grid_data', 'FCom_Cms_Admin_Controller_Blocks.grid_data')
            ->route('GET|POST /cms/blocks/form/:id', 'FCom_Cms_Admin_Controller_Blocks.form')
            ->route('GET|POST /cms/blocks/history/:id/grid_data', 'FCom_Cms_Admin_Controller_Blocks.history_grid_data')

            ->route('GET|POST /cms/forms', 'FCom_Cms_Admin_Controller_Forms.index')
            ->route('GET|POST /cms/forms/form/:id', 'FCom_Cms_Admin_Controller_Forms.form')
        ;

        BLayout::i()->addAllViews('Admin/views')
            ->view('cms/nav-tree-form', array('view_class'=>'FCom_Admin_View_Form'))
            ->view('cms/pages-form', array('view_class'=>'FCom_Admin_View_Form'))
            ->view('cms/blocks-form', array('view_class'=>'FCom_Admin_View_Form'))
        ;

        FCom_Admin_Model_Role::i()->createPermission(array(
            'cms' => 'CMS',
            'cms/pages' => 'Manage Pages',
            'cms/blocks' => 'Manage Blocks',
            'cms/nav' => 'Manage Navigation',
        ));
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'root', 'do'=>array(
                    array('addNav', 'cms', array('label'=>'CMS', 'pos'=>200)),
                    array('addNav', 'cms/nav', array('label'=>'Navigation', 'href'=>BApp::href('cms/nav'))),
                    array('addNav', 'cms/pages', array('label'=>'Pages', 'href'=>BApp::href('cms/pages'))),
                    array('addNav', 'cms/blocks', array('label'=>'Blocks', 'href'=>BApp::href('cms/blocks'))),
                    #array('addNav', 'cms/forms', array('label'=>'Form Actions', 'href'=>BApp::href('cms/forms'))),
                )),
            ),
            '/cms/nav'=>array(
                array('layout', 'base'),
                array('layout', 'form'),
                array('hook', 'main', 'views'=>array('cms/nav')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/nav'))),
            ),
            '/cms/nav/tree_form'=>array(
                array('root', 'cms/nav-tree-form'),
                array('view', 'cms/nav-tree-form',
                    'set'=>array(
                        'tab_view_prefix' => 'cms/nav-tree-form/',
                    ),
                    'do'=>array(
                        array('addTab', 'main', array('label'=>'Navigation Node', 'pos'=>10)),
                    ),
                ),
            ),
            '/cms/pages'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/pages')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/pages'))),
            ),
            '/cms/pages/form'=>array(
                array('layout', 'base'),
                array('layout', 'form'),
                array('hook', 'main', 'views'=>array('cms/pages-form')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/pages'))),
                array('view', 'cms/pages-form', 'set'=>array(
                    'tab_view_prefix' => 'cms/pages-form/',
                ), 'do'=>array(
                    array('addTab', 'main', array('label'=>'CMS Page', 'pos'=>10)),
                    array('addTab', 'history', array('label'=>'History', 'async'=>true, 'pos'=>20)),
                )),
            ),
            '/cms/blocks'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/blocks')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/blocks'))),
            ),
            '/cms/blocks/form'=>array(
                array('layout', 'base'),
                array('layout', 'form'),
                array('hook', 'main', 'views'=>array('cms/blocks-form')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/blocks'))),
                array('view', 'cms/blocks-form', 'set'=>array(
                    'tab_view_prefix' => 'cms/blocks-form/',
                ), 'do'=>array(
                    array('addTab', 'main', array('label'=>'CMS Block', 'pos'=>10)),
                    array('addTab', 'history', array('label'=>'History', 'async'=>true, 'pos'=>20)),
                )),
            ),
            '/cms/forms'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/forms')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/forms'))),
            ),
            '/cms/forms/form'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/forms-form')),
                array('view', 'root', 'do'=>array(array('setNav', 'cms/forms'))),
            ),
            '/settings'=>array(
                array('view', 'settings', 'do'=>array(
                    array('addTab', 'FCom_Cms', array('label'=>'CMS', 'async'=>true)),
                )),
            ),
        ));
    }

}

