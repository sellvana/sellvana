<?php

class FCom_ApiTest_ApiServer_V1_Test extends FCom_Admin_Controller_ApiServer_Abstract
{
    protected $_permission = 'system/api';

    public function action_list()
    {
        $data = array('One', 'Two', 'Three');
        BResponse::i()->json($data);
    }
}