<?php

class FCom_Admin extends BClass
{
    static public function bootstrap()
    {
        if (BRequest::i()->https()) {
            BResponse::i()->httpSTS();
        }

        FCom_Admin_Model_User::i();

        if (BApp::i()->get('area')==='FCom_Admin') {
            static::i()->bootstrapUI();
        }

        FCom_Admin_Model_Role::i()->createPermission(array(
            'system/users' => 'Manage Users',
            'system/roles' => 'Manage Roles and Permissions',
            'system/settings' => 'Update Settings',
            'system/modules' => 'Manage Modules',
        ));
        BPubSub::i()
            //->on('BActionController::beforeDispatch', 'FCom_Admin.onBeforeDispatch')
            ->on('FCom_Admin_Controller_Settings::action_index__POST', 'FCom_Admin.onSettingsPost')
        ;
    }

    public function bootstrapUI()
    {
        BRouting::i()
            ->route('_ /noroute', 'FCom_Admin_Controller.noroute', array(), null, false)
            ->get('/', 'FCom_Admin_Controller.index')
            ->get('/blank', 'FCom_Admin_Controller.blank')
            ->post('/login', 'FCom_Admin_Controller.login')
            ->any('/password/recover', 'FCom_Admin_Controller.password_recover')
            ->any('/password/reset', 'FCom_Admin_Controller.password_reset')
            ->get('/logout', 'FCom_Admin_Controller.logout')

            ->get('/my_account', 'FCom_Admin_Controller.my_account')
            ->get('/reports', 'FCom_Admin_Controller.reports')
            ->post('/my_account/personalize', 'FCom_Admin_Controller.personalize')

            ->get('/users', 'FCom_Admin_Controller_Users.index')
            ->any('/users/.action', 'FCom_Admin_Controller_Users')

            ->get('/roles', 'FCom_Admin_Controller_Roles.index')
            ->any('/roles/.action', 'FCom_Admin_Controller_Roles')

            ->any('/media/grid/:do', 'FCom_Admin_Controller_MediaLibrary.grid_data')

            ->any('/settings', 'FCom_Admin_Controller_Settings.index')

            ->any('/modules', 'FCom_Admin_Controller_Modules.index')
            ->post('/modules/migrate', 'FCom_Admin_Controller_Modules.migrate')

            ->get('/test', 'FCom_Admin_Controller.test')
        ;

        $defaultTheme = BConfig::i()->get('modules/FCom_Admin/theme');

        BLayout::i()
            ->defaultViewClass('FCom_Admin_View_Default')
            ->view('root', array('view_class'=>'FCom_Core_View_Root'))
            ->view('admin/header', array('view_class'=>'FCom_Admin_View_Header'))
            ->view('jqgrid', array('view_class'=>'FCom_Admin_View_Grid'))

            ->addAllViews('views')

            ->defaultTheme($defaultTheme ? $defaultTheme : 'FCom_Admin_DefaultTheme')
            ->afterTheme('FCom_Admin::layout')
        ;

        return $this;
    }

    public function onBeforeDispatch()
    {
    }

    public function onSettingsPost($args)
    {
        $db =& $args['post']['config']['db'];
        if (!empty($db['password']) && $db['password']==='*****') {
            unset($db['password']);
        }

        $ip = BRequest::i()->ip();
        foreach (array('Frontend','Admin') as $area) {
            if (!empty($args['post']['config']['modules']['FCom_'.$area]['mode_by_ip'])) {
                $modes =& $args['post']['config']['modules']['FCom_'.$area]['mode_by_ip'];
                $modes = str_replace('@', $ip, $modes);
                unset($modes);
            }
        }
    }

    public static function layout($args)
    {
        if (($head = BLayout::i()->view('head'))) {
            $config = BConfig::i()->get('modules/FCom_Admin');
            $addJs = !empty($config['add_js']) ? trim($config['add_js']) : null;
            if (!empty($addJs)) {
                foreach (explode("\n", $addJs) as $js) {
                    $head->js($js);
                }
            }
            $addCss = !empty($config['add_css']) ? trim($config['add_css']) : null;
            if (!empty($addCss)) {
                foreach (explode("\n", $addCss) as $css) {
                    $head->css($css);
                }
            }
        }
    }

    public static function href($url='')
    {
        return BApp::href($url, 1, 2);
    }

    public static function frontendHref($url='')
    {
        $r = BRequest::i();
        $href = $r->scheme().'://'.$r->httpHost().BConfig::i()->get('web/base_store');
        return trim(rtrim($href, '/').'/'.ltrim($url, '/'), '/');
    }
}
