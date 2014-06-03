<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Core_Controller_Abstract extends BActionController
{
    public function beforeDispatch()
    {
        if ($this->BRequest->csrf() && false == $this->isApiCall()) {
            $this->BResponse->status(403, 'Possible CSRF detected', 'Possible CSRF detected');
        }

        if (($root = $this->BLayout->view('root'))) {
            $root->body_class = $this->BRequest->path(0, 1);
        }
        return parent::beforeDispatch();
    }

    public function afterDispatch()
    {
        $this->BResponse->render();
    }

    /**
     * Apply current area theme and layouts supplied as parameter
     */
    public function layout($name = null)
    {
        $theme = $this->BConfig->get('modules/' . $this->BRequest->area() . '/theme');
        if (!$theme) {
            $theme = $this->BLayout->getDefaultTheme();
        }
        $layout = $this->BLayout;
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
