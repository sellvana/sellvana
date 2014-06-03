<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/');
    }

    public function action_static()
    {
        $this->viewProxy('static', 'index', 'main', 'base');
    }

    public function action_noroute()
    {
        $this->layout('404');
        $this->BResponse->status(404);
    }
}
