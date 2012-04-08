<?php

class FCom_Frontend extends BClass
{
    static public function bootstrap()
    {
        if (BRequest::i()->https()) {
            BResponse::i()->httpSTS();
        }

        BFrontController::i()
            ->route('GET /', 'FCom_Frontend_Controller.index')
        ;

        BLayout::i()
            ->view('root', array('view_class'=>'FCom_Frontend_View_Root'))
            //->view('head', array('view_class'=>'BViewHead'))

            ->addAllViews('views')

            ->defaultTheme('FCom_Frontend_DefaultTheme')
        ;

        if (BDebug::is('RECOVERY,MIGRATION')) {
            BLayout::i()->setRootView('under_construction');
            BResponse::i()->render();
        }
    }
}

class FCom_Frontend_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    public function messages($viewName, $namespace='frontend')
    {
        $this->view($viewName)->messages = BSession::i()->messages($namespace);
        return $this;
    }
}

class FCom_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/');
    }
}

class FCom_Frontend_View_Root extends FCom_Core_View_Root
{
    public function setLayoutClass($layout)
    {
        $this->layout_class = $layout;
        $this->show_left_col = $layout=='col2-layout-left' || $layout=='col3-layout';
        $this->show_right_col = $layout=='col2-layout-right' || $layout=='col3-layout';
        return $this;
    }

}