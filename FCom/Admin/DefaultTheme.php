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
                    array('hook', 'header', 'views'=>array('nav')),
                    array('view', 'head', 'do'=>array(
                        array('js', '{FCom_Core}/js/lib/jquery.js', array()),
                        array('js', '{FCom_Core}/js/lib/jquery-ui.js', array()),
                        array('js', '{FCom_Admin}/js/app.js', array()),
                    )),
                ),

                '/'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('home')),
                ),
            ));
        ;
    }
}