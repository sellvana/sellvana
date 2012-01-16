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
                    array('hook', 'main', 'views'=>array('header')),
                    //array('hook', 'footer', 'views'=>array('footer')),
                    //array('hook', 'main', 'views'=>array('breadcrumbs')),
                ),

                '/'=>array(

                ),
            ));
        ;
    }
}