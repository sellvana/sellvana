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
        BFrontController::i()
            ->route('GET /', 'FCom_Admin_Controller.index')
            ->route('GET /blank', 'FCom_Admin_Controller.blank')
            ->route('POST /login', 'FCom_Admin_Controller.login_post')
            ->route('GET /logout', 'FCom_Admin_Controller.logout')

            ->route('GET /my_account', 'FCom_Admin_Controller.my_account')
            ->route('GET /reports', 'FCom_Admin_Controller.reports')
            ->route('POST /my_account/personalize', 'FCom_Admin_Controller.personalize')

            ->route('GET /users', 'FCom_Admin_Controller_Users.index')
            ->route('GET|POST /users/.action', 'FCom_Admin_Controller_Users')

            ->route('GET /roles', 'FCom_Admin_Controller_Roles.index')
            ->route('GET|POST /roles/.action', 'FCom_Admin_Controller_Roles')

            ->route('GET|POST /media/grid/:do', 'FCom_Admin_Controller_MediaLibrary.grid_data')

            ->route('GET|POST /settings', 'FCom_Admin_Controller_Settings.index')

            ->route('GET|POST /modules', 'FCom_Admin_Controller_Modules.index')
            ->route('POST /modules/migrate', 'FCom_Admin_Controller_Modules.migrate')
        ;

        BLayout::i()
            ->defaultViewClass('FCom_Admin_View_Abstract')
            ->view('root', array('view_class'=>'FCom_Core_View_Root'))
            ->view('admin/header', array('view_class'=>'FCom_Admin_View_Header'))
            ->view('jqgrid', array('view_class'=>'FCom_Admin_View_Grid'))

            ->addAllViews('views')

            ->defaultTheme('FCom_Admin_DefaultTheme')
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
            if (!empty($config['add_js'])) {
                foreach (explode("\n", $config['add_js']) as $js) {
                    $head->js($js);
                }
            }
            if (!empty($config['add_css'])) {
                foreach (explode("\n", $config['add_css']) as $js) {
                    $head->css($css);
                }
            }
        }
    }

    public static function frontendHref($url='')
    {
        $r = BRequest::i();
        $href = $r->scheme().'://'.$r->httpHost().BConfig::i()->get('web/base_store');
        return trim($href.'/'.ltrim($url, '/'), '/');
    }
}

