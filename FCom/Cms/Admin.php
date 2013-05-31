<?php

class FCom_Cms_Admin extends BClass
{
    public static function bootstrap()
    {
        BRouting::i()
            ->get('/cms/nav', 'FCom_Cms_Admin_Controller_Nav.index')
            ->any('/cms/nav/.action', 'FCom_Cms_Admin_Controller_Nav')

            ->get('/cms/pages', 'FCom_Cms_Admin_Controller_Pages.index')
            ->any('/cms/pages/.action', 'FCom_Cms_Admin_Controller_Pages')
            ->any('/cms/pages/history/:id/grid_data', 'FCom_Cms_Admin_Controller_Pages.history_grid_data')

            ->get('/cms/blocks', 'FCom_Cms_Admin_Controller_Blocks.index')
            ->any('/cms/blocks/.action', 'FCom_Cms_Admin_Controller_Blocks')
            ->any('/cms/blocks/history/:id/grid_data', 'FCom_Cms_Admin_Controller_Blocks.history_grid_data')

            ->any('/cms/forms', 'FCom_Cms_Admin_Controller_Forms.index')
            ->any('/cms/forms/.action', 'FCom_Cms_Admin_Controller_Forms')
        ;

        BLayout::i()->addAllViews('Admin/views')
            ->afterTheme('FCom_Cms_Admin::layout')
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
                array('view', 'admin/header', 'do'=>array(
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
                array('view', 'admin/header', 'do'=>array(array('setNav', 'cms/nav'))),
            ),
            '/cms/nav/tree_form'=>array(
                array('root', 'cms/nav-tree-form'),
                array('view', 'cms/nav-tree-form',
                    'set'=>array(
                        'tab_view_prefix' => 'cms/nav-tree-form/',
                    ),
                    'do'=>array(
                        array('addTab', 'main', array('label'=>'Navigation Node', 'pos'=>10)),
                        array('addTab', 'content', array('label'=>'Page Content', 'pos'=>20)),
                    ),
                ),
            ),
            '/cms/pages'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('admin/grid')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'cms/pages'))),
            ),
            '/cms/pages/form'=>array(
                array('layout', 'base'),
                array('layout', 'form'),
                array('hook', 'main', 'views'=>array('admin/form')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'cms/pages'))),
                array('view', 'admin/form', 'set'=>array(
                    'tab_view_prefix' => 'cms/pages-form/',
                ), 'do'=>array(
                    array('addTab', 'main', array('label'=>'CMS Page', 'pos'=>10)),
                    array('addTab', 'history', array('label'=>'History', 'async'=>true, 'pos'=>20)),
                )),
            ),
            '/cms/blocks'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('admin/grid')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'cms/blocks'))),
            ),
            '/cms/blocks/form'=>array(
                array('layout', 'base'),
                array('layout', 'form'),
                array('hook', 'main', 'views'=>array('admin/form')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'cms/blocks'))),
                array('view', 'admin/form', 'set'=>array(
                    'tab_view_prefix' => 'cms/blocks-form/',
                ), 'do'=>array(
                    array('addTab', 'main', array('label'=>'CMS Block', 'pos'=>10)),
                    array('addTab', 'history', array('label'=>'History', 'async'=>true, 'pos'=>20)),
                )),
            ),
            '/cms/forms'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/forms')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'cms/forms'))),
            ),
            '/cms/forms/form'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('cms/forms-form')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'cms/forms'))),
            ),
            '/settings'=>array(
                array('view', 'settings', 'do'=>array(
                    array('addTab', 'FCom_Cms', array('label'=>'CMS', 'async'=>true)),
                )),
            ),
        ));
    }

}

