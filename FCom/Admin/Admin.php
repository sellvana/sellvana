<?php

class FCom_Admin_Admin extends BClass
{
    public static function beforeBootstrap()
    {
        $defaultTheme = BConfig::i()->get('modules/FCom_Admin/theme');
        BLayout::i()
            ->setDefaultTheme($defaultTheme ? $defaultTheme : 'FCom_Admin_DefaultTheme')
            ->setDefaultViewClass('FCom_Admin_View_Default')
        ;
    }

    public static function bootstrap()
    {
        FCom_Admin_Main::bootstrap();

        if (BRequest::i()->https()) {
            BResponse::i()->httpSTS();
        }

        BResponse::i()->nocache();
        //BConfig::i()->set('web/hide_script_name', 0);
    }

    public static function layout()
    {
        if (($head = BLayout::i()->view('head'))) {
            $head->js_raw('admin_init', '
FCom.Admin = {};
FCom.Admin.baseUrl = "' . rtrim(BConfig::i()->get('web/base_src'), '/') . '/' . '";
FCom.Admin.codemirrorBaseUrl = "' . BApp::src('@FCom_Admin/Admin/js/codemirror') . '";
FCom.Admin.upload_href = "' . BApp::href('upload') . '";
FCom.Admin.personalize_href = "' . BApp::href('my_account/personalize') . '";
            ');

            $config = BConfig::i()->get('modules/FCom_Admin');
            if (!empty($config['add_js_files'])) {
                foreach (explode("\n", $config['add_js_files']) as $js) {
                    $head->js(trim($js));
                }
            }
            if (!empty($config['add_js_code'])) {
                $head->js_raw('add_js_code', $config['add_js_code']);
            }
            if (!empty($config['add_css_files'])) {
                foreach (explode("\n", $config['add_css_files']) as $css) {
                    $head->css(trim($css));
                }
            }
            if (!empty($config['add_css_style'])) {
                $head->css_raw('add_css_code', $config['add_css_style']);
            }

            $pers = FCom_Admin_Model_User::i()->personalize();
            if (!empty($pers['nav']['collapsed'])) {
                BLayout::i()->view('root')->addBodyClass('main-nav-closed');
            }
        }
    }

    public function onSettingsPost($args)
    {
        if (!empty($args['post']['config']['db'])) {
            $db =& $args['post']['config']['db'];
            if (empty($db['password']) || $db['password'] === '*****') {
                unset($db['password']);
            }
        }

        $ip = BRequest::i()->ip();
        foreach (['Frontend', 'Admin'] as $area) {
            if (!empty($args['post']['config']['mode_by_ip']['FCom_' . $area])) {
                $modes =& $args['post']['config']['mode_by_ip']['FCom_' . $area];
                $modes = str_replace('@', $ip, $modes);
                unset($modes);
            }
        }
    }

    public function onGetDashboardWidgets($args)
    {
        $view = $args['view'];
        $view->addWidget('visitors-totals', [
            'title' => 'Visitors',
            'icon' => 'group',
            'view' => 'dashboard/visitors-totals',
            'cols' => 2,
            'async' => true,
        ]);
    }
}
