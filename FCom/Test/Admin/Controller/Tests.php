<?php
class FCom_Test_Admin_Controller_Tests extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/tests/index');
    }
}