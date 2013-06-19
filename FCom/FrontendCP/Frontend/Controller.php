<?php

class FCom_FrontendCP_Frontend_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_upload()
    {
        if (!FCom_Admin_Model_User::i()->sessionUser()->getPermission('frontendcp/edit')) {
            BResponse::i()->status(403);
        }
        $result = BRequest::i()->receiveFiles('image', BConfig::i()->get('fs/media_dir').'/tmp');
        $imgUrl = BConfig::i()->get('web/media_dir').'/tmp/'.$result['image']['name'];
        $imgUrl = FCom_Core_Main::i()->resizeUrl().'?f='.urlencode(ltrim($imgUrl, '/'));
        BResponse::i()->json(array('image'=>array('url'=>$imgUrl)));
    }

    public function action_update()
    {
        if (!FCom_Admin_Model_User::i()->sessionUser()->getPermission('frontendcp/edit')) {
            BResponse::i()->status(403);
        }
        $input = BRequest::i()->json();
        BResponse::i()->json($_SERVER);
    }
}
