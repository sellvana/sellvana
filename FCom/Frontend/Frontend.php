<?php

class FCom_Frontend extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route('GET /', 'FCom_Frontend_Controller.index')
        ;

        BLayout::i()
            ->view('root', array('view_class'=>'FCom_Frontend_View_Root'))
            //->view('head', array('view_class'=>'BViewHead'))
            ->allViews('views')
        ;

        BPubSub::i()->on('BActionController::beforeDispatch', 'FCom_Frontend.onBeforeDispatch');
    }

    public function onBeforeDispatch()
    {
        BLayout::i()->theme(BConfig::i()->get('FCom_Frontend/theme'));
    }
}

class FCom_Frontend_Controller extends BActionController
{
    public function action_index()
    {
        //FCom_Core::i()->writeDbConfig()->writeLocalConfig();
        BLayout::i()->layout('base')->layout('home');
        BResponse::i()->render();
    }
}

class FCom_Frontend_View_Root extends BView
{
    protected function _beforeRender()
    {
        $this->body_class = '';
        $this->layout_class = '';
        $this->show_left_col = '';
        $this->show_right_col = '';
        return true;
    }

    public function setLayoutClass($layout)
    {
        $this->layout_class = $layout;
        $this->show_left_col = $layout=='col2-left-layout' || $layout=='col3-layout';
        $this->show_right_col = $layout=='col2-right-layout' || $layout=='col3-layout';
    }
}