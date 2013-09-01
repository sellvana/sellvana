<?php

class FCom_PushServer_Controller extends FCom_Core_Controller_Abstract
{
    public function action_index__POST()
    {
        BResponse::i()->startLongResponse(false);

        $request = BRequest::i()->json();
        FCom_PushServer_Main::i()->dispatch($request);

        $client = FCom_PushServer_Model_Client::i()->sessionClient();
        $result = $client->dispatch();

        BResponse::i()->json($result);
    }
}
