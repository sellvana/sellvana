<?php

class FCom_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        BLayout::i()->layout('/');
        BResponse::i()->render();
    }

}