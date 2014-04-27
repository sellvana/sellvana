<?php

class FCom_Cms_Admin_Controller_Forms extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout( '/cms/forms' );
    }

    public function action_index__POST()
    {

    }

    public function action_form()
    {
        $this->layout( '/cms/forms/form' );
    }

    public function action_form__POST()
    {

    }
}
