<?php

class FCom_Admin_DefaultTheme extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()
            ->theme('FCom_Admin_DefaultTheme', array(
                'area' => 'FCom_Admin',
                'callback' => array(static::i(), 'layout'),
            ));
    }

    public function layout()
    {
        BLayout::i()
            ->layout(array(
                'jqgrid'=>array(
                    array('view', 'head', 'do'=>array(
                        array('css', '{FCom_Core}/js/lib/jqGrid/css/ui.jqgrid.css'),

                        array('css', '{FCom_Core}/js/lib/jqGrid/plugins/ui.multiselect.css'),
                        array('js', '{FCom_Core}/js/lib/jqGrid/plugins/ui.multiselect.js'),

                        array('js', '{FCom_Core}/js/lib/jqGrid/js/i18n/grid.locale-en.js'), // jqGrid translation
                        array('js', '{FCom_Core}/js/lib/jqGrid/dist/jquery.jqGrid.min.js'), // jqGrid translation
                        /*
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.base.js'), // jqGrid base
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.common.js'), // jqGrid common for editing
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.formedit.js'), // jqGrid Form editing
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.inlinedit.js'), // jqGrid inline editing
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.celledit.js'), // jqGrid cell editing
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.subgrid.js'), //jqGrid subgrid
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.treegrid.js'), //jqGrid treegrid
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.grouping.js'), //jqGrid grouping
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.custom.js'), //jqGrid custom
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.tbltogrid.js'), //jqGrid table to grid
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.import.js'), //jqGrid import
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/jquery.fmatter.js'), //jqGrid formater
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/JsonXml.js'), //xmljson utils
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.jqueryui.js'), //jQuery UI utils
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/grid.filter.js'), // filter Plugin
                        */
                        array('js', '{FCom_Core}/js/lib/jqGrid/plugins/jquery.tablednd.js'),
                        array('js', '{FCom_Core}/js/lib/jqGrid/plugins/jquery.contextmenu.js'),
                    )),
                ),
                'base'=>array(
                    array('root', 'root'),
                    array('hook', 'head', 'views'=>array('head')),
                    array('view', 'head', 'do'=>array(
                        array('icon', '/favicon.ico'),
                        array('js', '{FCom_Core}/js/lib/head.min.js'),
                        array('js', '{FCom_Core}/js/lib/json2.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.js'),
                        array('js', '{FCom_Core}/js/lib/jquery-ui.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.cookie.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.hotkeys.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.layout-latest.js'),
                        array('css', '{FCom_Core}/js/lib/css/smoothness/jquery-ui-1.8.17.custom.css'),
                    )),
                    array('layout', 'jqgrid'),
                    array('view', 'head', 'do'=>array(
                        array('js', '{FCom_Admin}/js/fcom.admin.js'),
                        array('css', '{FCom_Admin}/css/fcom.admin.css'),
                    )),
                    array('view', 'root', 'do'=>array(
                        array('addNav', 'home', array('label'=>'Home', 'href'=>BApp::m('FCom_Admin')->baseHref(), 'pos'=>10)),
                        array('addNav', 'admin', array('label'=>'Admin', 'pos'=>900)),
                        array('addNav', 'admin/users', array('label'=>'Users', 'href'=>BApp::m('FCom_Admin')->baseHref().'/users')),
                        array('addNav', 'admin/settings', array('label'=>'Settings', 'href'=>BApp::m('FCom_Admin')->baseHref().'/settings')),
                        array('addNav', 'admin/modules', array('label'=>'Modules', 'href'=>BApp::m('FCom_Admin')->baseHref().'/modules')),
                    )),
                ),
                'form'=>array(
                    array('view', 'head', 'do'=>array(
                        array('js', '{FCom_Core}/js/lib/ckeditor/ckeditor.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.jstree.js'),
                        array('css', '{FCom_Core}/js/lib/themes/default/style.css'),

                        array('js', '{FCom_Core}/js/lib/jquery.validationEngine-en.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.validationEngine.js'),
                        array('css', '{FCom_Core}/js/lib/css/jquery.validationEngine.css'),
                    )),
                ),
                '/'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('home')),
                    array('view', 'root', 'do'=>array(array('setNav', 'home'))),
                ),

                '/login'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('login')),
                ),

                '/my_account'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('my_account')),
                ),

                '/users'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('users')),
                    array('view', 'root', 'do'=>array(array('setNav', 'admin/users'))),
                ),
                '/users/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('view', 'root', 'do'=>array(array('setNav', 'admin/users'))),
                    array('hook', 'main', 'views'=>array('users/form')),
                    array('view', 'users/form', 'set'=>array('tab_view_prefix'=>'users/tab-'), 'do'=>array(
                        array('addTab', 'main', array('label'=>'General Info')),
                        array('addTab', 'history', array('label'=>'History')),
                    )),
                ),

                '/modules'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('modules')),
                    array('view', 'root', 'do'=>array(array('setNav', 'admin/modules'))),
                ),
            ));
        ;
    }
}