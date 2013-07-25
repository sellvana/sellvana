<?php

class FCom_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/');
    }

    public function action_noroute()
    {
        $this->layout('404');
        BResponse::i()->status(404);
    }
}
