<?php

class FCom_Core_Controller_Abstract extends BActionController
{
    public function beforeDispatch()
    {
        if (BRequest::i()->csrf() && false == static::i()->isApiCall()) {
            BResponse::i()->status(403, 'Possible CSRF detected', 'Possible CSRF detected');
        }

        if (($root = BLayout::i()->view('root'))) {
            $root->body_class = BRequest::i()->path(0, 1);
        }
        return parent::beforeDispatch();
    }

    public function afterDispatch()
    {
        BResponse::i()->render();
    }

    public function layout($name)
    {
        $theme = BConfig::i()->get('modules/'.BApp::i()->get('area').'/theme');
        if (!$theme) {
            $theme = BLayout::i()->getDefaultTheme();
        }
        $layout = BLayout::i();
        if ($theme) {
            $layout->applyTheme($theme);
        }
        foreach ((array)$name as $l) {
            $layout->applyLayout($l);
        }
        return $this;
    }

    public function messages($viewName, $namespace='frontend')
    {
        $this->view($viewName)->messages = BSession::i()->messages($namespace);
        return $this;
    }

    public function action_noroute()
    {
        $this->layout('404');
    }

    public function viewProxy($viewPrefix, $defaultView='index')
    {
        $viewPrefix = trim($viewPrefix, '/').'/';
        $page = BRequest::i()->params('view');
        if (!$page) {
            $page = $defaultView;
        }
        if (!$page || !($view = $this->view($viewPrefix.$page))) {
            $this->forward(false);
            return false;
        }
        $this->layout('base');
        BLayout::i()->applyLayout($viewPrefix.$page);
        $view->render();
        $metaData = $view->param('meta_data');
        if ($metaData && ($head = $this->view('head'))) {
            foreach ($metaData as $k=>$v) {
                $k = strtolower($k);
                switch ($k) {
                case 'title':
                    $head->addTitle($v); break;
                case 'meta_title': case 'meta_description': case 'meta_keywords':
                    $head->meta(str_replace('meta_','',$k), $v); break;
                }
            }
        }
        if (($root = BLayout::i()->view('root'))) {
            $root->addBodyClass('page-'.$page);
        }
        BLayout::i()->hookView('main', $viewPrefix.$page);
        return $page;
    }

    public function isApiCall()
    {
        return false;
    }
}
