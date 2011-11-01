<?php

class FCom_Frontend_DefaultTheme extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()->theme('FCom_Frontend_DefaultTheme', array(
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
                    array('hook', 'footer', 'views'=>array('footer')),
                    array('hook', 'main', 'views'=>array('breadcrumbs')),
                ),

                'home'=>array(
                    array('view', 'root', 'do'=>array(
                        array('setLayoutClass', 'col2-right-layout'),
                    ), 'set'=>array(
                        'body_class'=>'body-home',
                    )),
                    array('hook', 'main', 'views'=>array(
                        'promo/home_banner', 'promo/home_marketing', 'promo/weekly_specials',
                    )),
                    array('hook', 'sidebar-right', 'views'=>array(
                        'cart/block', 'compare/block', 'promo/recommended',
                    )),
                ),
            ));
        ;
    }
}