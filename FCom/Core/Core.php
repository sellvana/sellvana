<?php

class FCom_Core extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()
            ->defaultViewClass('FCom_Core_View_Abstract')
            ->view('head', array('view_class'=>'FCom_Core_View_Head'))
        ;
    }

    public function writeDbConfig()
    {
        $config = BConfig::i();
        $c = $config->get();
        $config->writeFile($c['config_dir'].'/db1.php', array('db'=>$c['db']));
        return $this;
    }

    public function writeLocalConfig()
    {
        $config = BConfig::i();
        $c = $config->get();
        // little clean up
        unset($c['db'], $c['config_dir'], $c['bootstrap']['depends']);
        foreach (array('Core', 'Admin', 'Frontend', 'Install', 'Cron') as $m) {
            if (($i = array_search('FCom_'.$m, $c['bootstrap']['modules']))) {
                unset($c['bootstrap']['modules'][$i]);
            }
        }
        $config->writeFile($c['config_dir'].'/local1.php', $c);
        return;
    }

    public function resizeUrl()
    {
        static $url;
        if (!$url) {
            $url = BConfig::i()->get('web/base_store').'/resize.php';
        }
        return $url;
    }
}

class FCom_Core_View_Abstract extends BView
{
    public function messagesHtml()
    {
        $html = '';
        if ($this->messages) {
            $html .= '<ul class="msgs">';
            foreach ($this->messages as $m) {
                $html .= '<li class="'.$m['type'].'-msg">'.$this->q($m['msg']).'</li>';
            }
            $html .= '</ul>';
        }
        return $html;
    }
}

class FCom_Core_View_Head extends BViewHead
{

}

class FCom_Core_Controller_Abstract extends BActionController
{
    public function beforeDispatch()
    {
        BLayout::i()->view('root')->bodyClass = BRequest::i()->path(0, 1);
        return parent::beforeDispatch();
    }

    public function afterDispatch()
    {
        BResponse::i()->render();
    }

    public function layout($name)
    {
        $theme = BConfig::i()->get('modules/'.FCom::i()->area().'/theme');
        $layout = BLayout::i();
        $layout->theme($theme);
        foreach ((array)$name as $l) {
            $layout->layout($l);
        }
        return $this;
    }

    public function messages($viewName, $namespace='frontend')
    {
        $this->view($viewName)->messages = BSession::i()->messages($namespace);
        return $this;
    }
}

class FCom_Core_Model_Abstract extends BModel
{

}