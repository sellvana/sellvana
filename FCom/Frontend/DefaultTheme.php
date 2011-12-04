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
                ),

                '/'=>array(
                    array('layout', 'base'),
                ),

                '/catalog/category'=>array(
                    array('layout', 'base'),
                ),

                '/catalog/product'=>array(
                    array('layout', 'base'),
                ),

                '/catalog/search'=>array(
                    array('layout', 'base'),
                ),

                '/catalog/compare'=>array(

                ),

                '/catalog/compare/ajax'=>array(

                ),
            ));
        ;
    }
}