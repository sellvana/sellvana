<?php

class FCom_Admin_Controller extends FCom_Admin_ControllerAbstract
{
    public function action_index()
    {
        BLayout::i()->layout('base')->layout('home');
        BResponse::i()->render();
    }

}