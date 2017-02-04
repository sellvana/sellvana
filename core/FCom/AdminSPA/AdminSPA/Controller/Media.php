<?php

class FCom_AdminSPA_AdminSPA_Controller_Media extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    public function action_upload__POST()
    {
        print_r($_POST);
        print_r($_FILES);
    }
}