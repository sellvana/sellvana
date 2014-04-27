<?php

class FCom_ApiTest_ApiServer_V1_Test extends FCom_ApiServer_Controller_Abstract
{
    protected $_authorizeActions = [ 'put' ];

    public function action_list()
    {
        $data = [ 'One', 'Two', 'Three' ];
        BResponse::i()->json( $data );
    }

    public function action_put()
    {
        $data = [ 'One', 'Two', 'Three' ];
        BResponse::i()->json( $data );
    }
}
