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

    /**
     * Apply current area theme and layouts supplied as parameter
     */
    public function layout($name = null)
    {
        $theme = BConfig::i()->get('modules/' . BApp::i()->get('area') . '/theme');
        if (!$theme) {
            $theme = BLayout::i()->getDefaultTheme();
        }
        $layout = BLayout::i();
        if ($theme) {
            $layout->applyTheme($theme);
        }
        if ($name) {
            foreach ((array)$name as $l) {
                $layout->applyLayout($l);
            }
        }
        return $this;
    }

    public function action_noroute()
    {
        $this->layout('404');
    }

    public function isApiCall()
    {
        return false;
    }
}
