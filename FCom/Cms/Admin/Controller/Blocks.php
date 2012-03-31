<?php

class FCom_Cms_Admin_Controller_Blocks extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/cms/blocks');
    }

    public function action_index__POST()
    {

    }

    public function action_form()
    {
        $this->layout('/cms/blocks/form');
    }

    public function action_form__POST()
    {

    }
}