<?php

class FCom_Frontend_DefaultTheme extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()
            ->theme('FCom_Frontend_DefaultTheme', array(
                'area' => 'FCom_Frontend',
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
                        array('icon', '/favicon.ico'),
                        array('js', '{FCom_Core}/js/lib/head.min.js'),
                        array('css', '{FCom_Frontend}/css/boilerplate_pre.css'),
                        //array('css', '{FCom_Frontend}/css/base.css'),
                        array('less', '{FCom_Frontend}/css/base.less'),
                        array('less', '{FCom_Frontend}/css/skin_default.less'),
                        array('css', 'boilerplate_post', array('file'=>'{FCom_Frontend}/css/boilerplate_post.css')),
                        array('js_raw', 'js_init', array('content'=>"window.less={env:'development'}")),
                        array('js', 'less', array('file'=>'{FCom_Core}/js/lib/less.min.js', 'separate'=>true)),
                        array('js', '{FCom_Core}/js/lib/jquery.min.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.validate.min.js'),
                        //array('js', '{FCom_Core}/js/lib/jquery-ui.min.js'),
                        //array('js', '{FCom_Core}/js/core.js'),
                    )),
                    array('hook', 'breadcrumbs', 'views'=>array('breadcrumbs')),
                    //array('hook', 'nav', 'views'=>array('nav')),
                    array('hook', 'header', 'views'=>array('header')),
                    array('hook', 'footer', 'views'=>array('footer')),
                ),

                '/'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('home')),
                ),
            ));
        ;
    }
}