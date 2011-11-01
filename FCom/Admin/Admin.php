<?php

class FCom_Admin extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route('GET /', 'FCom_Admin_Controller.index')
        ;

        BLayout::i()
            ->view('root', array('view_class'=>'FCom_Admin_View_Root'))
            //->view('head', array('view_class'=>'BViewHead'))
            ->allViews('views')
        ;

        BPubSub::i()->on('BActionController::beforeDispatch', 'FCom_Admin.onBeforeDispatch');
    }

    public function onBeforeDispatch()
    {
        BLayout::i()->theme(BConfig::i()->get('FCom_Admin/theme'));
    }
}

class FCom_Admin_ControllerAbstract extends BActionController
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
    protected function _beforeRender()
    {
        $this->body_class = '';
        $this->layout_class = '';
        $this->show_left_col = '';
        $this->show_right_col = '';
    }

    public function setLayoutClass($layout)
    {
        $this->layout_class = $layout;
        $this->show_left_col = $layout=='col2-left-layout' || $layout=='col3-layout';
        $this->show_right_col = $layout=='col2-right-layout' || $layout=='col3-layout';
    }
}