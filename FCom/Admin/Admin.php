<?php

class FCom_Admin_Admin extends BClass
{
    public static function bootstrap()
    {
    	FCom_Admin_Main::bootstrap();

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
            ->afterTheme('FCom_Admin_Admin::layout')
        ;
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

}