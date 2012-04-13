<?php

class FCom_Admin extends BClass
{
    static public function bootstrap()
    {
        if (BRequest::i()->https()) {
            BResponse::i()->httpSTS();
        }

        FCom_Admin_Model_User::i();

        BFrontController::i()
            ->route('GET /', 'FCom_Admin_Controller.index')
            ->route('GET /blank', 'FCom_Admin_Controller.blank')
            ->route('POST /login', 'FCom_Admin_Controller.login_post')
            ->route('GET /logout', 'FCom_Admin_Controller.logout')

            ->route('GET /my_account', 'FCom_Admin_Controller.my_account')
            ->route('GET /reports', 'FCom_Admin_Controller.reports')
            ->route('POST /my_account/personalize', 'FCom_Admin_Controller.personalize')

            ->route('GET /users', 'FCom_Admin_Controller_Users.index')
            ->route('GET|POST /users/grid_data', 'FCom_Admin_Controller_Users.grid_data')
            ->route('GET|POST /users/form/:id', 'FCom_Admin_Controller_Users.form')

            ->route('GET /roles', 'FCom_Admin_Controller_Roles.index')
            ->route('GET|POST /roles/grid_data', 'FCom_Admin_Controller_Roles.grid_data')
            ->route('GET|POST /roles/form/:id', 'FCom_Admin_Controller_Roles.form')
            ->route('GET|POST /roles/form/:id/tree_data', 'FCom_Admin_Controller_Roles.tree_data')

            ->route('GET|POST /media/grid/:do', 'FCom_Admin_Controller_MediaLibrary.grid_data')

            ->route('GET|POST /settings', 'FCom_Admin_Controller_Settings.index')

            ->route('GET|POST /modules', 'FCom_Admin_Controller_Modules.index')
            ->route('POST /modules/migrate', 'FCom_Admin_Controller_Modules.migrate')
        ;

        BLayout::i()
            ->defaultViewClass('FCom_Admin_View_Abstract')
            ->view('root', array('view_class'=>'FCom_Admin_View_Root'))
            ->view('jqgrid', array('view_class'=>'FCom_Admin_View_Grid'))

            ->addAllViews('views')

            ->defaultTheme('FCom_Admin_DefaultTheme')
        ;

        FCom_Admin_Model_Role::i()->createPermission(array(
            'admin/users' => 'Manage Users',
            'admin/roles' => 'Manage Roles and Permissions',
            'admin/settings' => 'Update Settings',
            'admin/modules' => 'Manage Modules',
        ));

        BPubSub::i()
            ->on('BActionController::beforeDispatch', 'FCom_Admin.onBeforeDispatch')
            ->on('FCom_Admin_Controller_Settings::action_index__POST', 'FCom_Admin.onSettingsPost')
        ;

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
}

