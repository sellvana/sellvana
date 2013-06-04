<?php

class FCom_Admin_Admin extends BClass
{
    public static function beforeBootstrap()
    {
        $defaultTheme = BConfig::i()->get('modules/FCom_Admin/theme');
        BLayout::i()
            ->defaultTheme($defaultTheme ? $defaultTheme : 'FCom_Admin_DefaultTheme')
            ->addView('root', array('view_class'=>'FCom_Core_View_Root'))
            ->addView('admin/header', array('view_class'=>'FCom_Admin_View_Header'))
            ->addView('admin/nav', array('view_class'=>'FCom_Admin_View_Nav'))
            ->addView('jqgrid', array('view_class'=>'FCom_Admin_View_Grid'))
            //->defaultViewClass('FCom_Admin_View_Default')
        ;
    }

    public static function bootstrap()
    {
        FCom_Admin_Main::bootstrap();

        if (BRequest::i()->https()) {
            BResponse::i()->httpSTS();
        }

        BLayout::i()->afterTheme('FCom_Admin_Admin::layout');
    }

    public static function layout($args)
    {
        if (($head = BLayout::i()->view('head'))) {
            $config = BConfig::i()->get('modules/FCom_Admin');
            if (!empty($config['add_js_files'])) {
                foreach (explode("\n", $config['add_js_files']) as $js) {
                    $head->js(trim($js));
                }
            }
            if (!empty($config['add_js_code'])) {
                $head->js_raw($config['add_js_code']);
            }
            if (!empty($config['add_css_files'])) {
                foreach (explode("\n", $config['add_css_files']) as $css) {
                    $head->css(trim($css));
                }
            }
            if (!empty($config['add_css_style'])) {
                $head->css_raw($config['add_css_style']);
            }
        }
    }

}