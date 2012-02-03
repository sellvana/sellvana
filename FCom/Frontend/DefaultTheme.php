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
            ->rootView('root')
            ->layout(array(
                'base'=>array(
                    array('hook', 'head', 'views'=>array('head')),
                    array('hook', 'header', 'views'=>array('header')),
                    array('hook', 'breadcrumbs', 'views'=>array('breadcrumbs')),
                    //array('hook', 'nav', 'views'=>array('nav')),
                    array('hook', 'footer', 'views'=>array('footer')),
                    array('view', 'head', 'do'=>array(
                        array('js', '{FCom_Core}/js/lib/head.min.js', array()),
                    )),
                ),

                '/'=>array(
                    array('layout', 'base'),
                ),
            ));
        ;
    }
}