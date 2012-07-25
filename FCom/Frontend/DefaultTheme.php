<?php

class FCom_Frontend_DefaultTheme extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()
            ->addTheme('FCom_Frontend_DefaultTheme', array(
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
                        array('meta', 'Content-Type', 'text/html; charset=UTF-8', true),
                        array('icon', '/favicon.ico'),
                        array('js', '{FCom_Core}/js/lib/head.min.js'),
                        array('js', '{FCom_Core}/js/lib/html5shiv.min.js', array('if'=>'lt IE 9')),
                        array('css', '{FCom_Frontend}/css/boilerplate_pre.css'),
                        //array('css', '{FCom_Frontend}/css/base.css'),
                        array('less', '{FCom_Frontend}/css/base.less'),
                        array('less', '{FCom_Frontend}/css/skin_default.less'),
                        array('css', 'boilerplate_post', array('file'=>'{FCom_Frontend}/css/boilerplate_post.css')),
                        array('css', 'pnotify', array('file'=>'{FCom_Core}/js/lib/css/jquery.pnotify.default.css')),
                        array('css', 'rating', array('file'=>'{FCom_Core}/js/lib/css/jquery.rating.css')),
                        array('css', 'lightbox', array('file'=>'{FCom_Core}/js/lib/css/lightbox/lightbox.css')),
                        array('js_raw', 'js_init', array('content'=>"window.less={env:'development'}")),
                        array('js_raw', 'js_base_href', array('content'=>"Fcom={base_href:'".BApp::baseUrl()."'}")),
                        array('js', 'less', array('file'=>'{FCom_Core}/js/lib/less.min.js', 'separate'=>true)),
                        array('js', '{FCom_Core}/js/lib/jquery.min.js'),
                        //array('js', '{FCom_Core}/js/lib/jquery-ui.min.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.validate.min.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.pnotify.min.js'),
                        array('js', '{FCom_Core}/js/lib/jquery.rating.min.js'),
                        array('js', '{FCom_Core}/js/lib/lightbox/lightbox.js'),

                        //array('js', '{FCom_Core}/js/core.js'),
                    )),
                    array('hook', 'breadcrumbs', 'views'=>array('breadcrumbs')),
                    //array('hook', 'nav', 'views'=>array('nav')),
                    array('hook', 'header', 'views'=>array('header')),
                    array('hook', 'footer', 'views'=>array('footer')),
                ),

                '404'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('404')),
                ),
                '/denied'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('denied')),
                ),

                '/'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('home')),
                ),
            ));
        ;
    }
}