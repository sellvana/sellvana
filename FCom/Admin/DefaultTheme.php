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
                'base'=>array(
                    array('root', 'root'),
                    array('hook', 'head', 'views'=>array('head')),
                    array('view', 'head', 'do'=>array(
                        array('js', '{FCom_Core}/js/lib/jquery.js', array()),
                        array('js', '{FCom_Core}/js/lib/jquery-ui.js', array()),
                        array('css', '{FCom_Core}/js/lib/css/smoothness/jquery-ui-1.8.17.custom.css', array()),
                        array('css', '{FCom_Core}/js/lib/jqGrid/css/ui.jqgrid.css', array()),
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/i18n/grid.locale-en.js', array()),
                        array('js', '{FCom_Core}/js/lib/jqGrid/js/jquery.jqGrid.min.js', array()),
                        array('js', '{FCom_Admin}/js/app.js', array()),
                        array('css', '{FCom_Admin}/css/styles.css', array()),
                    )),
                    array('view', 'root', 'do'=>array(
                        array('navAdd', 'home', array('label'=>'Home', 'href'=>BApp::m('FCom_Admin')->baseHref(), 'key'=>'')),
                    )),
                ),

                '/'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('home')),
                ),

                '/login'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('login')),
                ),
            ));
        ;
    }
}