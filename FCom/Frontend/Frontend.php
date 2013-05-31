<?php

class FCom_Frontend_Frontend extends BClass
{

    static public function bootstrap()
    {
        FCom_Frontend_Main::bootstrap();

        BRouting::i()
            ->route('_ /noroute', 'FCom_Frontend_Controller.noroute', array(), null, false)
            ->route('GET /', 'FCom_Frontend_Controller.index')
        ;

        $defaultTheme = BConfig::i()->get('modules/FCom_Frontend/theme');

        BLayout::i()
            ->view('root', array('view_class'=>'FCom_Frontend_View_Root'))
            ->view('breadcrumbs', array('view_class'=>'FCom_Frontend_View_Breadcrumbs'))
            //->view('head', array('view_class'=>'BViewHead'))

            ->addAllViews('views')

            ->defaultTheme($defaultTheme ? $defaultTheme : 'FCom_Frontend_DefaultTheme')
            ->afterTheme('FCom_Frontend_Frontend::layout')
        ;
    }

    public static function layout($args)
    {
        if (($head = BLayout::i()->view('head'))) {
            $config = BConfig::i()->get('modules/FCom_Frontend');
            if (!empty($config['add_js'])) {
                foreach (explode("\n", $config['add_js']) as $js) {
                    $head->js($js);
                }
            }
            if (!empty($config['add_css'])) {
                foreach (explode("\n", $config['add_css']) as $css) {
                    $head->css($css);
                }
            }
        }
    }
}