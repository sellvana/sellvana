<?php

class FCom_Admin extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route('GET /', 'FCom_Admin_Controller.index')
            ->route('GET /blank', 'FCom_Admin_Controller.blank')
        ;

        BLayout::i()
            ->view('root', array('view_class'=>'FCom_Admin_View_Root'))
            ->view('nav', array('view_class' => 'FCom_Admin_View_Nav'))
            //->view('head', array('view_class'=>'BViewHead'))
            ->allViews('views')
        ;

        BPubSub::i()->on('BActionController::beforeDispatch', 'FCom_Admin.onBeforeDispatch');
    }

    public function onBeforeDispatch()
    {
        BLayout::i()->theme(BConfig::i()->get('modules/FCom_Admin/theme'));
    }
}

class FCom_Admin_Controller_Abstract extends BActionController
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;
        return true;
    }

    public function afterDispatch()
    {

    }
}

class FCom_Admin_View_Root extends BView
{

}

class FCom_Admin_View_Nav extends BView
{

}