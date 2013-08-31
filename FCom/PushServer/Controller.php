<?php

class FCom_PushServer_Controller extends FCom_Core_Controller_Abstract
{
    public function action_index__POST()
    {
        set_time_limit(0);
        $request = BRequest::i()->json();
        $client = FCom_Comet_Model_Client::i()->sessionClient();
        $result = $client->dispatch($request);
        BResponse::i()->json($result);
    }
}
