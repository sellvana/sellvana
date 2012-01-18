<?php

class FCom_Admin extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route('GET /', 'FCom_Admin_Controller.index')
            ->route('GET /blank', 'FCom_Admin_Controller.blank')
            ->route('POST /login', 'FCom_Admin_Controller.login_post')
            ->route('GET /logout', 'FCom_Admin_Controller.logout')
        ;

        BLayout::i()
            ->view('root', array('view_class'=>'FCom_Admin_View_Root'))
            ->view('head', array('view_class'=>'FCom_Admin_View_Head'))
            ->view('grid', array('view_class'=>'BViewGrid'))
            ->allViews('views')
        ;

        BPubSub::i()->on('BActionController::beforeDispatch', 'FCom_Admin.onBeforeDispatch');
    }

    public function onBeforeDispatch()
    {
    }
}

class FCom_Admin_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    public function authorize($args=array())
    {
        return FCom_Admin_Model_User::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_unauthorized()
    {
        $this->layout('/login');
    }
}

class FCom_Admin_View_Head extends BViewHead
{

}
