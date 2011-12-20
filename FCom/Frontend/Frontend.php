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
    }

    public function loadTheme()
    {
        BLayout::i()->theme(BConfig::i()->get('modules/FCom_Frontend/theme'));
    }
}

class FCom_Frontend_Controller_Abstract extends FCom_Core_Controller_Abstract
{
}

class FCom_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        //FCom_Core::i()->writeDbConfig()->writeLocalConfig();
        $this->layout('/');
        BResponse::i()->render();
    }
}

class FCom_Frontend_View_Root extends BView
{
    public function setLayoutClass($layout)
    {
        $this->layout_class = $layout;
        $this->show_left_col = $layout=='col2-layout-left' || $layout=='col3-layout';
        $this->show_right_col = $layout=='col2-layout-right' || $layout=='col3-layout';
        return $this;
    }

    public function addBodyClass($class)
    {
        $this->body_class = !$this->body_class ? (array)$class
            : array_merge($this->body_class, (array)$class);
        return $this;
    }

    public function getBodyClass()
    {
        return $this->body_class ? join(' ', $this->body_class) : '';
    }
}