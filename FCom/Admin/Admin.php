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

            ->route('GET /users', 'FCom_Admin_Controller_Users.index')
            ->route('GET /users/grid_data', 'FCom_Admin_Controller_Users.grid_data')
            ->route('POST /users/grid_data', 'FCom_Admin_Controller_Users.grid_post')
            ->route('GET /users/form/:id', 'FCom_Admin_Controller_Users.form')
            ->route('GET /users/form_tab/:id', 'FCom_Admin_Controller_Users.form_tab')
            ->route('POST /users/form/:id', 'FCom_Admin_Controller_Users.form_post')

            ->route('GET /media/grid/:do', 'FCom_Admin_Controller_MediaLibrary.grid_get')
            ->route('POST /media/grid/:do', 'FCom_Admin_Controller_MediaLibrary.grid_post')

            ->route('GET /modules', 'FCom_Admin_Controller_Modules.index')
        ;

        BLayout::i()
            ->view('root', array('view_class'=>'FCom_Admin_View_Root'))
            ->view('head', array('view_class'=>'FCom_Admin_View_Head'))
            ->view('jqgrid', array('view_class'=>'FCom_Admin_View_Grid'))

            ->view('users/form', array('view_class'=>'FCom_Admin_View_Form'))

            ->allViews('views')

            ->defaultTheme('FCom_Admin_DefaultTheme')
        ;

        BPubSub::i()->on('BActionController::beforeDispatch', 'FCom_Admin.onBeforeDispatch');
    }

    public function onBeforeDispatch()
    {
    }
}

class FCom_Admin_View_Head extends BViewHead
{

}
