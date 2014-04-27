<?php

class FCom_PushServer_Controller extends FCom_Core_Controller_Abstract
{
    public function action_index__POST()
    {
        BResponse::i()->nocache()->startLongResponse( false );
$timer = microtime( true );
        $client = FCom_PushServer_Model_Client::i()->sessionClient();

        $request = BRequest::i()->json();

        $client->processRequest( $request )->checkIn()->waitForMessages()->checkOut();

        $result = [
            'conn_id' => $request[ 'conn_id' ],
            'messages' => $client->getMessages(),
        ];

        BResponse::i()->json( $result );
    }
}
