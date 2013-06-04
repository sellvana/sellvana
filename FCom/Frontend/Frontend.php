<?php

class FCom_Frontend_Frontend extends BClass
{
    static public function beforeBootstrap()
    {
        $defaultTheme = BConfig::i()->get('modules/FCom_Frontend/theme');
        BLayout::i()
            ->defaultTheme($defaultTheme ? $defaultTheme : 'FCom_Frontend_DefaultTheme')
            ->addView('root', array('view_class'=>'FCom_Frontend_View_Root'))
            ->addView('breadcrumbs', array('view_class'=>'FCom_Frontend_View_Breadcrumbs'))
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

    public static function defaultThemeCustomLayout()
    {
        $cookieConfig = BConfig::i()->get('cookie');

        BLayout::i()
            ->addLayout(array(
                'base'=>array(
                    array('view', 'head', 'do'=>array(
                        array('js_raw', 'js_init', array('content'=>"
window.less={env:'development'};
head(function() {
    $.cookie.options = ".BUtil::toJson(array('domain'=>$cookieConfig['domain'], 'path'=>$cookieConfig['path'])).";
    $('.select2').select2({width:'other values', minimumResultsForSearch:20});
});
FCom = {};
FCom.base_href = '".BApp::baseUrl()."';
")),
                    )),
                 ),
             ));
    }
}