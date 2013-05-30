<?php

class FCom_Admin_DefaultTheme extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()
            ->addTheme('FCom_Admin_DefaultTheme', array(
                'area' => 'FCom_Admin',
                'callback' => array(static::i(), 'layout'),
            ));
    }

    public function layout()
    {
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('root', 'root'),
                    array('hook', 'head', 'views'=>array('head')),
                    array('view', 'head', 'do'=>array(
                        array('meta', 'Content-Type', 'text/html; charset=UTF-8', true),
                        array('icon', BConfig::i()->get('web/base_src').'/favicon.ico'),
                        array('js', '{FCom_Core}/js/lib/jquery.min.js'),
                        array('js', '{FCom_Core}/js/lib/head.min.js'),
                        array('js', '{FCom_Core}/js/lib/es5-shim.min.js', array('if'=>'lt IE 9')),
                        array('js', '{FCom_Core}/js/lib/json2.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.min.js'),
                        array('js', '{FCom_Core}/js/lib/jquery-ui.min.js'),
                        array('js', '{FCom_Core}/js/lib/angular.min.js'),
                        //array('js', '{FCom_Core}/js/lib/jquery.ba-hashchange.min.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.cookie.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.hotkeys.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.layout-latest.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.pnotify.min.js'),
                        array('css', '{FCom_Core}/js/lib/css/smoothness/jquery-ui-1.8.17.custom.css'),
                        array('css', '{FCom_Core}/js/lib/css/jquery.pnotify.default.css'),
                        //highcharts
                        array('js', '{FCom_Admin}/js/highcharts/highcharts.js'),
                    )),
                    array('layout', 'jqgrid'),
                    array('layout', 'jstree'),
                    array('view', 'head', 'do'=>array(
                        array('js', '{FCom_Admin}/js/fcom.admin.js'),
                        array('css', '{FCom_Admin}/css/fcom.admin.css'),
                    )),
                    array('view', 'admin/header', 'do'=>array(
                        array('addNav', 'home', array('label'=>'Dashboard', 'href'=>BApp::href(), 'pos'=>10)),
                        array('addNav', 'system', array('label'=>'System', 'pos'=>900)),
                        array('addNav', 'system/users', array('label'=>'Users', 'href'=>BApp::href('/users'))),
                        array('addNav', 'system/roles', array('label'=>'Roles & Permissions', 'href'=>BApp::href('/roles'))),
                        array('addNav', 'system/settings', array('label'=>'Settings', 'href'=>BApp::href('/settings'))),
                        array('addNav', 'system/modules', array('label'=>'Installed Modules', 'href'=>BApp::href('/modules'))),
                        array('addShortcut', 'system/users', array('label'=>'New User', 'href'=>BApp::href('/users/form/'))),
                    )),
                ),
                '404'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('404')),
                ),
                'jqgrid'=>array(
                    array('view', 'head', 'do'=>array(
                        array('css', '{FCom_Core}/js/lib/jqGrid/ui.jqgrid.css'),
                        array('css', '{FCom_Core}/js/lib/jqGrid/plugins/ui.multiselect.css'),
                        array('js', '{FCom_Core}/js/lib/jqGrid/plugins/ui.multiselect.js'),
                        array('js', '{FCom_Core}/js/lib/jqGrid/i18n/grid.locale-en.js'), // jqGrid translation
                        array('js', '{FCom_Core}/js/lib/jqGrid/jquery.jqGrid.min.js'),
                        /*
                        array('css', '{FCom_Core}/js/lib/jqGrid/css/ui.jqgrid.css'),

                        array('css', '{FCom_Core}/js/lib/jqGrid/plugins/ui.multiselect.css'),
                        array('js', '{FCom_Core}/js/lib/jqGrid/plugins/ui.multiselect.js'),

                        array('js', '{FCom_Core}/js/lib/jqGrid/js/i18n/grid.locale-en.js'), // jqGrid translation
                        array('js', '{FCom_Core}/js/lib/jqGrid/dist/jquery.jqGrid.min.js'),
                        */
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
                'jstree'=>array(
                    array('view', 'head', 'do'=>array(
                        array('css', '{FCom_Core}/js/lib/themes/default/style.css'),
                        array('js', '{FCom_Core}/js/lib/jquery.jstree.js'),
                    )),
                ),
                'mcdropdown'=>array(
                    array('view', 'head', 'do'=>array(
                        array('css', '{FCom_Core}/js/lib/css/jquery.mcdropdown.css'),
                        array('js', '{FCom_Core}/js/lib/jquery.mcdropdown.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.bgiframe.js'),
                    )),
                ),
                'form'=>array(
                    array('view', 'head', 'do'=>array(
                        array('js', '{FCom_Core}/js/lib/ckeditor/ckeditor.js'),
                        array('js', '{FCom_Core}/js/lib/ckeditor/adapters/jquery.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.jstree.js'),
                        array('css', '{FCom_Core}/js/lib/themes/default/style.css'),

                        array('js', '{FCom_Core}/js/lib/jquery.validate.min.js'),
                    )),
                ),
                '/'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('home')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'home'))),
                ),
                '/denied'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('denied')),
                ),

                '/login'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('login')),
                ),
                '/password/recover'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('password/recover')),
                ),
                '/password/reset'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('password/reset')),
                ),

                '/my_account'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('my_account')),
                ),
                '/reports'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('reports')),
                ),

                '/users'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('admin/grid')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'admin/users'))),
                ),
                '/users/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'admin/users'))),
                    array('hook', 'main', 'views'=>array('admin/form')),
                    array('view', 'admin/form', 'set'=>array('tab_view_prefix'=>'users-form/'), 'do'=>array(
                        array('addTab', 'main', array('label'=>'General Info')),
                        array('addTab', 'history', array('label'=>'History')),
                    )),
                ),

                '/roles'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('admin/grid')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'admin/roles'))),
                ),
                '/roles/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'admin/roles'))),
                    array('hook', 'main', 'views'=>array('admin/form')),
                    array('view', 'admin/form', 'set'=>array('tab_view_prefix'=>'roles-form/'), 'do'=>array(
                        array('addTab', 'main', array('label'=>'Permissions')),
                        array('addTab', 'users', array('label'=>'Users')),
                    )),
                ),

                '/settings'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('settings')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'admin/settings'))),
                    array('view', 'settings', 'set'=>array('tab_view_prefix'=>'settings/'), 'do'=>array(
                        array('addTab', 'FCom_Core', array('label'=>'Fulleron Core', 'async'=>true)),
                        array('addTab', 'FCom_Admin', array('label'=>'Fulleron Admin', 'async'=>true)),
                        array('addTab', 'FCom_Frontend', array('label'=>'Fulleron Frontend', 'async'=>true)),
                    )),
                ),

                '/modules'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('modules')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'admin/modules'))),
                ),
            ));
        ;
    }
}