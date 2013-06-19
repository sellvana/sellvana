<?php

class FCom_Frontend_Frontend extends BClass
{
    static public function beforeBootstrap()
    {
        $defaultTheme = BConfig::i()->get('modules/FCom_Frontend/theme');
        BLayout::i()
            ->defaultTheme($defaultTheme ? $defaultTheme : 'FCom_Frontend_DefaultTheme')
        ;
    }

    public static function bootstrap()
    {
        if (BRequest::i()->https()) {
            BResponse::i()->httpSTS();
        }

        if (BDebug::is('RECOVERY,MIGRATION')) {
            BLayout::i()->setRootView('under_construction');
            BResponse::i()->render();
        }

        BLayout::i()->afterTheme('FCom_Frontend_Frontend::layout');
    }

    public static function layout($args)
    {
        if (($head = BLayout::i()->view('head'))) {
            $config = BConfig::i()->get('modules/FCom_Frontend');
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
