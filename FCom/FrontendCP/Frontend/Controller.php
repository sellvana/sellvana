<?php

class FCom_FrontendCP_Frontend_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_update()
    {
        $input = BRequest::i()->json();
        BResponse::i()->json($input);
    }
}
